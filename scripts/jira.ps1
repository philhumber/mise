<#
.SYNOPSIS
    JIRA CLI for Wine App project using REST API v3

.DESCRIPTION
    Simple JIRA operations: list issues, create, update, transition status.
    Credentials stored in ../wineapp-config/jira.config.json

.EXAMPLE
    .\jira.ps1 list                     # List open issues
    .\jira.ps1 get MISE-123              # Get issue details
    .\jira.ps1 create "Bug title"       # Create new task
    .\jira.ps1 status MISE-123 "Done"    # Transition status
    .\jira.ps1 comment MISE-123 "Note"   # Add comment
#>

param(
    [Parameter(Position=0)]
    [ValidateSet("list", "get", "create", "status", "comment", "sprint", "help")]
    [string]$Command = "help",

    [Parameter(Position=1)]
    [string]$Arg1,

    [Parameter(Position=2)]
    [string]$Arg2,

    [Parameter(Position=3)]
    [string]$Arg3
)

# Load config
$configPath = Join-Path $PSScriptRoot "..\..\mise-config\jira.config.json"
if (-not (Test-Path $configPath)) {
    Write-Host "Config not found at: $configPath" -ForegroundColor Red
    Write-Host "Create jira.config.json with: email, token, baseUrl" -ForegroundColor Yellow
    Write-Host "Get API token from: https://id.atlassian.com/manage-profile/security/api-tokens" -ForegroundColor Gray
    exit 1
}

$config = Get-Content $configPath | ConvertFrom-Json
$project = "MISE"
$baseUrl = "$($config.baseUrl)/rest/api/3"
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
        [string]$Endpoint,
        [object]$Body
    )

    $url = "$baseUrl/$Endpoint"
    $params = @{
        Uri = $url
        Method = $Method
        Headers = $headers
    }

    if ($Body) {
        $params.Body = ($Body | ConvertTo-Json -Depth 10)
    }

    try {
        $response = Invoke-RestMethod @params
        return $response
    }
    catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "Error ($statusCode): $($_.Exception.Message)" -ForegroundColor Red
        if ($_.ErrorDetails.Message) {
            $errorDetail = $_.ErrorDetails.Message | ConvertFrom-Json -ErrorAction SilentlyContinue
            if ($errorDetail.errorMessages) {
                $errorDetail.errorMessages | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
            }
        }
        return $null
    }
}

function Show-Help {
    Write-Host "`nJIRA CLI for mise" -ForegroundColor Cyan
    Write-Host "=====================`n" -ForegroundColor Cyan
    Write-Host "Usage: .\jira.ps1 <command> [args]`n"
    Write-Host "Commands:" -ForegroundColor Yellow
    Write-Host "  list [status]           List issues (default: open). Status: open, done, all"
    Write-Host "  get <key>               Get issue details (e.g., MISE-123)"
    Write-Host "  create <summary> [type] Create issue. Type: Task, Bug, Story (default: Task)"
    Write-Host "  status <key> <status>   Change status: 'To Do', 'In Progress', 'Done'"
    Write-Host "  comment <key> <text>    Add comment to issue"
    Write-Host "  sprint                  Show current sprint issues"
    Write-Host "  help                    Show this help`n"
    Write-Host "Examples:" -ForegroundColor Yellow
    Write-Host "  .\jira.ps1 list"
    Write-Host "  .\jira.ps1 get MISE-124"
    Write-Host "  .\jira.ps1 create `"Fix login bug`" Bug"
    Write-Host "  .\jira.ps1 status MISE-124 `"In Progress`""
    Write-Host "  .\jira.ps1 comment MISE-124 `"Started working on this`""
    Write-Host ""
}

function Get-Issues {
    param([string]$Status = "open")

    $jql = "project = $($project)"

    switch ($Status.ToLower()) {
        "open" { $jql += " AND statusCategory != Done" }
        "done" { $jql += " AND statusCategory = Done" }
        "all" { } # No filter
    }

    $jql += " ORDER BY created DESC"
    $encoded = [System.Web.HttpUtility]::UrlEncode($jql)

    $result = Invoke-JiraApi -Endpoint "search/jql?jql=$encoded&maxResults=50&fields=key,summary,status,issuetype,priority"

    if ($result) {
        Write-Host "`n$($project) Issues ($Status):" -ForegroundColor Cyan
        Write-Host ("-" * 80)

        if ($result.issues.Count -eq 0) {
            Write-Host "No issues found." -ForegroundColor Gray
        }

        foreach ($issue in $result.issues) {
            $status = $issue.fields.status.name
            $statusColor = switch ($issue.fields.status.statusCategory.key) {
                "done" { "Green" }
                "indeterminate" { "Yellow" }
                default { "White" }
            }
            $type = $issue.fields.issuetype.name.Substring(0,1)

            Write-Host "$($issue.key)" -ForegroundColor White -NoNewline
            Write-Host " [$type]" -ForegroundColor Gray -NoNewline
            Write-Host " $($issue.fields.summary)" -NoNewline
            Write-Host " - " -NoNewline
            Write-Host "$status" -ForegroundColor $statusColor
        }
        Write-Host ""
    }
}

function Get-Issue {
    param([string]$Key)

    if (-not $Key) {
        Write-Host "Usage: .\jira.ps1 get <issue-key>" -ForegroundColor Yellow
        return
    }

    $result = Invoke-JiraApi -Endpoint "issue/$Key"

    if ($result) {
        Write-Host "`n$($result.key): $($result.fields.summary)" -ForegroundColor Cyan
        Write-Host ("-" * 60)
        Write-Host "Type:     $($result.fields.issuetype.name)"
        Write-Host "Status:   $($result.fields.status.name)"
        Write-Host "Priority: $($result.fields.priority.name)"

        if ($result.fields.description) {
            Write-Host "`nDescription:" -ForegroundColor Yellow
            # ADF to plain text (simplified)
            if ($result.fields.description.content) {
                foreach ($block in $result.fields.description.content) {
                    if ($block.content) {
                        $text = ($block.content | Where-Object { $_.text } | ForEach-Object { $_.text }) -join ""
                        if ($text) { Write-Host "  $text" }
                    }
                }
            }
        }

        Write-Host "`nURL: $($config.host)/browse/$($result.key)" -ForegroundColor Gray
        Write-Host ""
    }
}

function New-Issue {
    param(
        [string]$Summary,
        [string]$Type = "Task"
    )

    if (-not $Summary) {
        Write-Host "Usage: .\jira.ps1 create <summary> [type]" -ForegroundColor Yellow
        Write-Host "Types: Task, Bug, Story" -ForegroundColor Gray
        return
    }

    $body = @{
        fields = @{
            project = @{ key = $project }
            summary = $Summary
            issuetype = @{ name = $Type }
        }
    }

    $result = Invoke-JiraApi -Method "POST" -Endpoint "issue" -Body $body

    if ($result) {
        Write-Host "`nCreated: $($result.key)" -ForegroundColor Green
        Write-Host "URL: $($config.host)/browse/$($result.key)" -ForegroundColor Gray
        Write-Host ""
    }
}

function Set-IssueStatus {
    param(
        [string]$Key,
        [string]$StatusName
    )

    if (-not $Key -or -not $StatusName) {
        Write-Host "Usage: .\jira.ps1 status <issue-key> <status>" -ForegroundColor Yellow
        Write-Host "Status: 'To Do', 'In Progress', 'Done'" -ForegroundColor Gray
        return
    }

    # Get available transitions
    $transitions = Invoke-JiraApi -Endpoint "issue/$Key/transitions"

    if (-not $transitions) { return }

    $targetTransition = $transitions.transitions | Where-Object { $_.name -eq $StatusName -or $_.to.name -eq $StatusName }

    if (-not $targetTransition) {
        Write-Host "Invalid status '$StatusName'. Available transitions:" -ForegroundColor Yellow
        $transitions.transitions | ForEach-Object { Write-Host "  - $($_.name) -> $($_.to.name)" }
        return
    }

    $body = @{
        transition = @{ id = $targetTransition.id }
    }

    $result = Invoke-JiraApi -Method "POST" -Endpoint "issue/$Key/transitions" -Body $body

    Write-Host "`n$Key transitioned to: $StatusName" -ForegroundColor Green
    Write-Host ""
}

function Add-Comment {
    param(
        [string]$Key,
        [string]$Text
    )

    if (-not $Key -or -not $Text) {
        Write-Host "Usage: .\jira.ps1 comment <issue-key> <comment-text>" -ForegroundColor Yellow
        return
    }

    # ADF format for comment
    $body = @{
        body = @{
            type = "doc"
            version = 1
            content = @(
                @{
                    type = "paragraph"
                    content = @(
                        @{
                            type = "text"
                            text = $Text
                        }
                    )
                }
            )
        }
    }

    $result = Invoke-JiraApi -Method "POST" -Endpoint "issue/$Key/comment" -Body $body

    if ($result) {
        Write-Host "`nComment added to $Key" -ForegroundColor Green
        Write-Host ""
    }
}

function Get-Sprint {
    # Get issues in current sprint
    $jql = "project = $($project) AND sprint in openSprints() ORDER BY status ASC, priority DESC"
    $encoded = [System.Web.HttpUtility]::UrlEncode($jql)

    $result = Invoke-JiraApi -Endpoint "search/jql?jql=$encoded&maxResults=50&fields=key,summary,status,issuetype,priority"

    if ($result) {
        Write-Host "`nCurrent Sprint Issues:" -ForegroundColor Cyan
        Write-Host ("-" * 80)

        if ($result.issues.Count -eq 0) {
            Write-Host "No issues in current sprint." -ForegroundColor Gray
        }

        foreach ($issue in $result.issues) {
            $status = $issue.fields.status.name
            $statusColor = switch ($issue.fields.status.statusCategory.key) {
                "done" { "Green" }
                "indeterminate" { "Yellow" }
                default { "White" }
            }
            $type = $issue.fields.issuetype.name.Substring(0,1)

            Write-Host "$($issue.key)" -ForegroundColor White -NoNewline
            Write-Host " [$type]" -ForegroundColor Gray -NoNewline
            Write-Host " $($issue.fields.summary)" -NoNewline
            Write-Host " - " -NoNewline
            Write-Host "$status" -ForegroundColor $statusColor
        }
        Write-Host ""
    }
}

# Main
Add-Type -AssemblyName System.Web

switch ($Command) {
    "list"    { Get-Issues -Status $(if ($Arg1) { $Arg1 } else { "open" }) }
    "get"     { Get-Issue -Key $Arg1 }
    "create"  { New-Issue -Summary $Arg1 -Type $(if ($Arg2) { $Arg2 } else { "Task" }) }
    "status"  { Set-IssueStatus -Key $Arg1 -StatusName $Arg2 }
    "comment" { Add-Comment -Key $Arg1 -Text $Arg2 }
    "sprint"  { Get-Sprint }
    "help"    { Show-Help }
    default   { Show-Help }
}
