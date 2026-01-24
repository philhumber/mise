<#
.SYNOPSIS
    Batch create JIRA issues for Mise project from JSON file

.DESCRIPTION
    Creates all epics, stories, and tasks from jira-tasks.json
    Assigns story points and sprint assignments

.EXAMPLE
    .\jira-batch-create.ps1
    .\jira-batch-create.ps1 -DryRun
#>

param(
    [switch]$DryRun = $false
)

# Load config
$scriptRoot = $PSScriptRoot
$configPath = Join-Path $scriptRoot "..\..\mise-config\jira.config.json"
$tasksPath = Join-Path $scriptRoot "jira-tasks.json"

if (-not (Test-Path $configPath)) {
    Write-Host "Config not found at: $configPath" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $tasksPath)) {
    Write-Host "Tasks file not found at: $tasksPath" -ForegroundColor Red
    exit 1
}

$config = Get-Content $configPath | ConvertFrom-Json
$tasks = Get-Content $tasksPath -Raw | ConvertFrom-Json
$project = "MISE"
$baseUrl = "$($config.baseUrl)/rest/api/3"
$agileUrl = "$($config.baseUrl)/rest/agile/1.0"
$authString = "$($config.email):$($config.token)"
$authBytes = [System.Text.Encoding]::UTF8.GetBytes($authString)
$authBase64 = [Convert]::ToBase64String($authBytes)

$headers = @{
    "Authorization" = "Basic $authBase64"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

function Invoke-JiraApi {
    param(
        [string]$Method = "GET",
        [string]$Url,
        [object]$Body
    )

    $params = @{
        Uri = $Url
        Method = $Method
        Headers = $headers
    }

    if ($Body) {
        $params.Body = ($Body | ConvertTo-Json -Depth 10 -Compress)
    }

    try {
        $response = Invoke-RestMethod @params
        return $response
    }
    catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "  Error ($statusCode): $($_.Exception.Message)" -ForegroundColor Red
        if ($_.ErrorDetails.Message) {
            Write-Host "  $($_.ErrorDetails.Message)" -ForegroundColor Red
        }
        return $null
    }
}

function Get-BoardId {
    $result = Invoke-JiraApi -Url "$agileUrl/board?projectKeyOrId=$project"
    if ($result -and $result.values.Count -gt 0) {
        return $result.values[0].id
    }
    return $null
}

function Get-OrCreateSprint {
    param([string]$SprintName, [int]$BoardId)

    # Check existing sprints
    $sprints = Invoke-JiraApi -Url "$agileUrl/board/$BoardId/sprint?state=future,active"

    if ($sprints -and $sprints.values) {
        $existing = $sprints.values | Where-Object { $_.name -eq $SprintName }
        if ($existing) {
            return $existing.id
        }
    }

    # Create new sprint
    $body = @{
        name = $SprintName
        originBoardId = $BoardId
    }

    $result = Invoke-JiraApi -Method "POST" -Url "$agileUrl/sprint" -Body $body
    if ($result) {
        return $result.id
    }
    return $null
}

function Create-Issue {
    param(
        [string]$Summary,
        [string]$Type = "Task",
        [string]$Description = "",
        [int]$StoryPoints = 0,
        [string]$EpicKey = $null
    )

    $fields = @{
        project = @{ key = $project }
        summary = $Summary
        issuetype = @{ name = $Type }
    }

    # Add description if provided
    if ($Description) {
        $fields.description = @{
            type = "doc"
            version = 1
            content = @(
                @{
                    type = "paragraph"
                    content = @(
                        @{
                            type = "text"
                            text = $Description
                        }
                    )
                }
            )
        }
    }

    # Add epic link if provided (using parent field for next-gen projects)
    if ($EpicKey) {
        $fields.parent = @{ key = $EpicKey }
    }

    $body = @{ fields = $fields }

    $result = Invoke-JiraApi -Method "POST" -Url "$baseUrl/issue" -Body $body

    if ($result) {
        # Set story points (custom field - may need adjustment based on your JIRA config)
        if ($StoryPoints -gt 0) {
            # Try to update story points (field ID varies by JIRA instance)
            # Common field IDs: customfield_10016, customfield_10026, etc.
            $updateBody = @{
                fields = @{
                    customfield_10016 = $StoryPoints  # Adjust field ID as needed
                }
            }
            Invoke-JiraApi -Method "PUT" -Url "$baseUrl/issue/$($result.key)" -Body $updateBody | Out-Null
        }
        return $result
    }
    return $null
}

# Main execution
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Mise JIRA Batch Import" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "[DRY RUN MODE - No changes will be made]`n" -ForegroundColor Yellow
}

# Get board ID
$boardId = Get-BoardId
if (-not $boardId) {
    Write-Host "Could not find JIRA board for project $project" -ForegroundColor Red
    exit 1
}
Write-Host "Found board ID: $boardId`n" -ForegroundColor Green

# Track created epics
$epicKeys = @{}

# Step 1: Create Epics
Write-Host "Creating Epics..." -ForegroundColor Yellow
Write-Host ("-" * 50)

foreach ($epic in $tasks.epics) {
    if ($DryRun) {
        Write-Host "  [DRY] Would create Epic: $($epic.name)" -ForegroundColor Gray
        $epicKeys[$epic.key] = "MISE-DRY"
    } else {
        Write-Host "  Creating Epic: $($epic.name)..." -NoNewline
        $result = Create-Issue -Summary $epic.name -Type "Epic" -Description $epic.description
        if ($result) {
            Write-Host " $($result.key)" -ForegroundColor Green
            $epicKeys[$epic.key] = $result.key
        } else {
            Write-Host " FAILED" -ForegroundColor Red
        }
    }
}

Write-Host ""

# Step 2: Create Tasks by Sprint
$totalCreated = 0
$totalFailed = 0

foreach ($sprint in $tasks.sprints) {
    Write-Host "`nSprint: $($sprint.name)" -ForegroundColor Cyan
    Write-Host ("-" * 50)

    # Get or create sprint
    $sprintId = $null
    if (-not $DryRun) {
        $sprintId = Get-OrCreateSprint -SprintName $sprint.name -BoardId $boardId
        if ($sprintId) {
            Write-Host "  Sprint ID: $sprintId" -ForegroundColor Green
        }
    }

    $sprintIssueKeys = @()

    foreach ($task in $sprint.tasks) {
        $epicKey = $epicKeys[$task.epic]

        if ($DryRun) {
            Write-Host "  [DRY] Would create: $($task.summary) ($($task.points)sp)" -ForegroundColor Gray
        } else {
            Write-Host "  Creating: $($task.summary)..." -NoNewline
            $result = Create-Issue -Summary $task.summary -Type $task.type -StoryPoints $task.points -EpicKey $epicKey
            if ($result) {
                Write-Host " $($result.key)" -ForegroundColor Green
                $sprintIssueKeys += $result.key
                $totalCreated++
            } else {
                Write-Host " FAILED" -ForegroundColor Red
                $totalFailed++
            }
            Start-Sleep -Milliseconds 200  # Rate limiting
        }
    }

    # Move issues to sprint
    if ($sprintId -and $sprintIssueKeys.Count -gt 0) {
        Write-Host "  Moving issues to sprint..." -NoNewline
        $moveBody = @{
            issues = $sprintIssueKeys
        }
        $moveResult = Invoke-JiraApi -Method "POST" -Url "$agileUrl/sprint/$sprintId/issue" -Body $moveBody
        Write-Host " Done" -ForegroundColor Green
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Import Complete" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Created: $totalCreated issues" -ForegroundColor Green
if ($totalFailed -gt 0) {
    Write-Host "  Failed:  $totalFailed issues" -ForegroundColor Red
}
Write-Host "`n  View at: $($config.baseUrl)/jira/software/projects/$project/boards/$boardId/backlog" -ForegroundColor Gray
Write-Host ""
