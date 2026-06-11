# CAERT — post-deploy smoke tests (Windows / PowerShell)
param(
    [string]$BaseUrl = "http://127.0.0.1:8000"
)

$ErrorActionPreference = "Stop"

function Test-Endpoint {
    param([string]$Path, [int[]]$Expected)
    $uri = "$BaseUrl$Path"
    try {
        $r = Invoke-WebRequest -Uri $uri -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue
        $code = [int]$r.StatusCode
    } catch {
        if ($_.Exception.Response) {
            $code = [int]$_.Exception.Response.StatusCode
        } else {
            throw
        }
    }
    if ($Expected -notcontains $code) {
        throw "FAIL $Path returned $code (expected $($Expected -join ','))"
    }
    Write-Host "OK $Path ($code)"
}

Write-Host "Smoke test: $BaseUrl"
Test-Endpoint "/health" @(200)
Test-Endpoint "/login" @(200)
Test-Endpoint "/" @(302, 401, 403)
Write-Host "Smoke tests passed."
