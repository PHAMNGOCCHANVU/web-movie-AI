$utf8NoBom = New-Object System.Text.UTF8Encoding $false
$f = 'postman/collections/Web Movie AI - Full API/Movies/Get Homepage Blocks.request.yaml'
$bytes = [System.IO.File]::ReadAllBytes($f)
if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
  $bytes = $bytes[3..($bytes.Length-1)]
}
$text = [System.Text.Encoding]::UTF8.GetString($bytes)
$text = $text.Replace("`r`n", "`n")
[System.IO.File]::WriteAllText($f, $text, $utf8NoBom)
Write-Host "Fixed: $f"
Write-Host "Done!"
