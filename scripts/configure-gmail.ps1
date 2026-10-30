$ErrorActionPreference = 'Stop'
$senderEmail = Read-Host 'Gmail sender address'
$senderSecret = Read-Host 'Google App Password (input hidden)' -AsSecureString
$secretPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($senderSecret)
try {
    $senderPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($secretPointer)
    $mailSettings = @{ email = $senderEmail; password = $senderPassword }
    $phpExe = 'C:\xampp\php\php.exe'
    $setupScript = Join-Path $PSScriptRoot 'configure-mail.php'
    $mailSettings | ConvertTo-Json -Compress | & $phpExe $setupScript
    if ($LASTEXITCODE -ne 0) { throw 'Gmail setup was not completed.' }
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($secretPointer)
    $senderPassword = $null
    $mailSettings = $null
}
