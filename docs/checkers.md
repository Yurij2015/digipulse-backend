# Monitoring Checkers

This document describes how each check type is implemented in the DigiPulse Go worker.

## 1. HTTP Checker (`http`)

- **Mechanism**: Performs an `HTTP GET` request to the target URL.
- **Headers**: Includes a standard `User-Agent` (DigiPulse/1.0) and `Accept` header to minimize false-positives from anti-bot protections.
- **Health Logic**: 
  - **Up**: Any HTTP response with status code `< 500` (includes 2xx, 3xx, and 4xx like 401/403).
  - **Down**: Status codes `>= 500` (Internal Server Error) or network-level failures (Timeout, DNS, Connection Refused).
- **Timeout**: The request will time out after **10 seconds**.

## 2. SSL Certificate Checker (`ssl`)

- **Mechanism**: Establishes a TCP connection with TLS handshake on port 443.
- **Health Logic**:
  - **Up**: The certificate is valid and not expired.
  - **Down**: The certificate has expired or the connection failed.
- **Metadata**: Returns the certificate issuer, expiration date, and days remaining.

## 3. DNS Checker (`dns`)

- **Mechanism**: Performs a DNS lookup for the domain name.
- **Health Logic**:
  - **Up**: At least one IP address was successfully resolved.
  - **Down**: DNS resolution failed.
- **Metadata**: Returns the list of resolved IP addresses.

## 4. Port Checker (`port`)

- **Mechanism**: Attempts to establish a TCP connection to a specific port.
- **Health Logic**:
  - **Up**: The port is open and reachable.
  - **Down**: Connection refused or timed out.
- **Parameters**: Requires a `port` parameter (defaults to 443 if not provided).
