param(
    [string]$OutputDir = "dist",
    [string]$ZipName = ""
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
$resolvedOutputDir = if ([System.IO.Path]::IsPathRooted($OutputDir)) {
    $OutputDir
} else {
    Join-Path $projectRoot $OutputDir
}

if ([string]::IsNullOrWhiteSpace($ZipName)) {
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $ZipName = "emarioh-catering-hostinger-$timestamp.zip"
}

if (-not $ZipName.EndsWith(".zip", [System.StringComparison]::OrdinalIgnoreCase)) {
    $ZipName += ".zip"
}

$stagingRoot = Join-Path $resolvedOutputDir "_hostinger-package"
$zipPath = Join-Path $resolvedOutputDir $ZipName

$includeItems = @(
    ".htaccess",
    "api",
    "app",
    "assets",
    "database",
    "docs",
    "public",
    "storage"
)

$excludeDirectoryNames = @(
    ".git",
    ".vscode"
)

$excludeFilePatterns = @(
    "^storage[\\/]+sessions[\\/]+sess_",
    "^storage[\\/]+backups[\\/].+\.sql$"
)

function Test-ExcludedRelativePath {
    param(
        [string]$RelativePath,
        [bool]$IsDirectory
    )

    $normalizedPath = $RelativePath -replace "\\", "/"

    foreach ($directoryName in $excludeDirectoryNames) {
        if ($normalizedPath -eq $directoryName -or $normalizedPath.StartsWith("$directoryName/")) {
            return $true
        }
    }

    if (-not $IsDirectory) {
        foreach ($pattern in $excludeFilePatterns) {
            if ($normalizedPath -match $pattern) {
                return $true
            }
        }
    }

    return $false
}

function Copy-Tree {
    param(
        [string]$SourcePath,
        [string]$RelativePath = ""
    )

    $item = Get-Item -LiteralPath $SourcePath -Force

    if (Test-ExcludedRelativePath -RelativePath $RelativePath -IsDirectory $item.PSIsContainer) {
        return
    }

    $destinationPath = if ($RelativePath -eq "") {
        $stagingRoot
    } else {
        Join-Path $stagingRoot $RelativePath
    }

    if ($item.PSIsContainer) {
        New-Item -ItemType Directory -Path $destinationPath -Force | Out-Null

        $children = Get-ChildItem -LiteralPath $item.FullName -Force
        foreach ($child in $children) {
            $childRelativePath = if ($RelativePath -eq "") {
                $child.Name
            } else {
                Join-Path $RelativePath $child.Name
            }

            Copy-Tree -SourcePath $child.FullName -RelativePath $childRelativePath
        }

        return
    }

    $parentPath = Split-Path -Parent $destinationPath
    if ($parentPath -and -not (Test-Path -LiteralPath $parentPath)) {
        New-Item -ItemType Directory -Path $parentPath -Force | Out-Null
    }

    Copy-Item -LiteralPath $item.FullName -Destination $destinationPath -Force
}

New-Item -ItemType Directory -Path $resolvedOutputDir -Force | Out-Null

if (Test-Path -LiteralPath $stagingRoot) {
    Remove-Item -LiteralPath $stagingRoot -Recurse -Force
}

if (Test-Path -LiteralPath $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

New-Item -ItemType Directory -Path $stagingRoot -Force | Out-Null

foreach ($relativeItem in $includeItems) {
    $sourcePath = Join-Path $projectRoot $relativeItem

    if (-not (Test-Path -LiteralPath $sourcePath)) {
        Write-Warning "Skipped missing path: $relativeItem"
        continue
    }

    Copy-Tree -SourcePath $sourcePath -RelativePath $relativeItem
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stagingRoot, $zipPath)

$includedFiles = Get-ChildItem -LiteralPath $stagingRoot -Recurse -Force | Where-Object { -not $_.PSIsContainer }
$includedDirectories = Get-ChildItem -LiteralPath $stagingRoot -Recurse -Force | Where-Object { $_.PSIsContainer }

Write-Output "Hostinger package created:"
Write-Output $zipPath
Write-Output ("Files included: {0}" -f $includedFiles.Count)
Write-Output ("Directories included: {0}" -f $includedDirectories.Count)
Write-Output "Includes app/config.local.php and hidden .htaccess files when present."
Write-Output "Excludes .git, .vscode, runtime session files, and SQL backups from storage/backups."
