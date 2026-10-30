$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$releaseRoot = Join-Path $projectRoot 'releases'
$stageRoot = Join-Path (Join-Path $projectRoot 'var') ('package-' + [guid]::NewGuid().ToString('N'))
$packageRoot = Join-Path $stageRoot 'SmallGrocery'
$utf8 = New-Object System.Text.UTF8Encoding($false)
New-Item -ItemType Directory -Path $releaseRoot,$packageRoot -Force | Out-Null

# Generate complete SQL imports from the canonical schema/migration.
$schema = [IO.File]::ReadAllText((Join-Path $projectRoot 'database\schema.sql'))
$hosting = "-- FRESH HOSTING INSTALL ONLY. Select the database created by your hosting control panel first.`n-- No CREATE DATABASE or USE: compatible with provider-prefixed database names.`n-- Contains schema and lookup seeds only; no accounts or business records.`n" + $schema
$fresh = @'
-- FRESH INSTALL ONLY. Contains schema and lookup seeds, no accounts or business data.
-- Do not import over an existing working grocery database.
CREATE DATABASE IF NOT EXISTS `mini_grocery` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mini_grocery`;

'@ + $schema
$update = @'
-- UPDATE 002: select your existing grocery database in phpMyAdmin before importing.
-- Adds email OTP registration without deleting accounts or transactions. Safe to rerun.
SET @grocery_column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='email');
SET @grocery_ddl = IF(@grocery_column_exists=0,'ALTER TABLE users ADD COLUMN email VARCHAR(254) NULL UNIQUE','SELECT 1');
PREPARE grocery_update FROM @grocery_ddl;
EXECUTE grocery_update;
DEALLOCATE PREPARE grocery_update;
SET @grocery_column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='email_verified_at');
SET @grocery_ddl = IF(@grocery_column_exists=0,'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL','SELECT 1');
PREPARE grocery_update FROM @grocery_ddl;
EXECUTE grocery_update;
DEALLOCATE PREPARE grocery_update;

'@ + [IO.File]::ReadAllText((Join-Path $projectRoot 'database\migrations\002-email-registration.sql'))
foreach ($targetRoot in @((Join-Path $projectRoot 'database'),$releaseRoot)) {
    [IO.File]::WriteAllText((Join-Path $targetRoot 'mini_grocery_fresh.sql'),$fresh,$utf8)
    [IO.File]::WriteAllText((Join-Path $targetRoot 'mini_grocery_hosting_schema.sql'),$hosting,$utf8)
    [IO.File]::WriteAllText((Join-Path $targetRoot 'mini_grocery_update_002_email_otp.sql'),$update,$utf8)
}

# Allowlisted sources: local config, var/, releases/, .git/ never enter the package.
$rootFiles = @('.htaccess','.gitignore','AGENTS.md','README.md','SETUP-XAMPP.md','CHANGELOG.md','config.php','config.local.example.php','config.hosting.example.php')
foreach ($relative in $rootFiles) { Copy-Item -LiteralPath (Join-Path $projectRoot $relative) -Destination (Join-Path $packageRoot $relative) }
foreach ($directory in @('app','public','database','scripts','tests','docs','vendor')) {
    $source = Join-Path $projectRoot $directory
    foreach ($file in Get-ChildItem -LiteralPath $source -File -Recurse) {
        $relative = $file.FullName.Substring($projectRoot.Length + 1)
        if ($relative -match '(^|[\\/])__pycache__([\\/]|$)' -or $file.Extension -eq '.pyc' -or $file.Name -in @('config.local.php','first-owner.sql')) { continue }
        $destination = Join-Path $packageRoot $relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $destination) -Force | Out-Null
        Copy-Item -LiteralPath $file.FullName -Destination $destination
    }
}
$entries = @(Get-ChildItem -LiteralPath $packageRoot -Recurse -File | ForEach-Object {
    @{ path = $_.FullName.Substring($packageRoot.Length + 1).Replace('\','/'); sha256 = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash; bytes = $_.Length }
} | Sort-Object { $_.path })
$stamp = [TimeZoneInfo]::ConvertTimeBySystemTimeZoneId([DateTime]::UtcNow,'Singapore Standard Time').ToString('yyyyMMdd-HHmmss-fff')
$manifest = @{ generated_manila = $stamp; table_count = 16; contents = 'Source + schema only; private configuration and live data excluded'; files = $entries } | ConvertTo-Json -Depth 5
[IO.File]::WriteAllText((Join-Path $packageRoot 'RELEASE-MANIFEST.json'),$manifest,$utf8)
Add-Type -AssemblyName System.IO.Compression.FileSystem
$versionedZip = Join-Path $releaseRoot ('SmallGrocery-' + $stamp + '.zip')
[IO.Compression.ZipFile]::CreateFromDirectory($stageRoot,$versionedZip)
$latestZip = Join-Path $releaseRoot 'SmallGrocery-Latest.zip'
Copy-Item -LiteralPath $versionedZip -Destination $latestZip -Force
$hostingVersionedZip = Join-Path $releaseRoot ('SmallGrocery-htdocs-' + $stamp + '.zip')
[IO.Compression.ZipFile]::CreateFromDirectory($packageRoot,$hostingVersionedZip)
$hostingZip = Join-Path $releaseRoot 'SmallGrocery-htdocs.zip'
Copy-Item -LiteralPath $hostingVersionedZip -Destination $hostingZip -Force
Write-Output "Project ZIP: $latestZip"
Write-Output "Direct htdocs ZIP: $hostingZip"
Write-Output "Versioned ZIP: $versionedZip"
Write-Output "Fresh SQL: $(Join-Path $releaseRoot 'mini_grocery_fresh.sql')"
Write-Output "Update SQL: $(Join-Path $releaseRoot 'mini_grocery_update_002_email_otp.sql')"
Write-Output "Packaged files: $($entries.Count) plus manifest. Private/runtime data excluded."
