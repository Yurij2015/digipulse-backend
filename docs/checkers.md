# Site Verification Methods (Checkers)

This document provides a technical overview of how DigiPulse verifies site availability and performance across different check types. These checks are executed by the Go-based monitor service.

## 1. HTTP Status Checker (`http`)

The primary check for web applications and websites. It ensures the web server is responsive and serving content correctly.

- **Mechanism**: Performs an `HTTP HEAD` request to the target URL.
- **Why HEAD?**: It retrieves only the headers, not the full page body, making it extremely fast and lightweight for the monitored server.
- **Success Criteria**:
  - The request must complete within **10 seconds**.
  - The response status code must be in the range **200-399** (includes successes and redirects).
- **Failure**: Any network error, timeout, or status code $\ge$ 400 will mark the site as `down`.

## 2. SSL Certificate Checker (`ssl`)

Ensures your site's traffic remains encrypted and that users don't see "Connection not private" warnings.

- **Mechanism**: Establishes a TLS connection to the host on port **443**.
- **Logic**:
  - Automatically strips protocols (`http://`, `https://`) and paths from the URL to isolate the hostname.
  - Inspects the peer certificate presented during the handshake.
- **Success Criteria**:
  - The certificate must not be expired.
  - A successful TLS handshake must be established within **5 seconds**.
- **Metadata Provided**:
  - `issuer`: The Common Name of the certificate issuer (e.g., "GTS CA 1P3").
  - `days_remaining`: Numeric count of days until expiration.
  - `expires_at`: ISO 8601 timestamp of expiration.
- **Warning State**: While the status is reported as `up` if valid, the system tracks when expiry is less than 7 days for future notifications.

## 3. DNS Lookup Checker (`dns`)

Verifies that your domain name is correctly configured and pointing to the intended servers.

- **Mechanism**: Performs a standard system DNS lookup (`net.LookupIP`) for the hostname.
- **Success Criteria**:
  - The system must resolve at least one IP address (A or AAAA record).
- **Metadata Provided**:
  - `ips`: A list of all IP addresses associated with the domain.
- **Use Case**: Detecting "DNS Hijacking" or configuration errors at the registrar level.

## 4. Port Reachability Checker (`port`)

A low-level network check to see if a specific service is running and accessible from the outside.

- **Mechanism**: Attempts to open a TCP socket (`net.DialTimeout`) to the host on a specified port.
- **Parameters**:
  - `port`: The TCP port to check (e.g., `3306` for MySQL, `5432` for Postgres, `22` for SSH).
- **Default**: Defaults to port `443` if no port is specified in the configuration.
- **Success Criteria**:
  - The TCP handshake must complete successfully within **5 seconds**.
- **Failure**: If the connection is refused, timed out, or the host is unreachable.

---

### Comparison Summary

| Checker | Level | Key Metric | Resource Usage |
| :--- | :--- | :--- | :--- |
| **HTTP** | Application | Status Code | Low |
| **SSL** | Security | Days to Expiry | Medium |
| **DNS** | Infrastructure | IP Resolution | Very Low |
| **Port** | Network | TCP Connectivity | Low |
