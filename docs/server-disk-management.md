# Server Disk Management

Procedures for diagnosing and resolving disk space issues on the Ubuntu/LVM production server.

---

## 1. Diagnose disk usage

### `df -h`
Shows free/used space for all mounted filesystems. `-h` = human-readable sizes.

```bash
df -h
```

Key column: `Use%` — if it hits 100%, services that write to disk (PostgreSQL, Docker) will crash.

**Docs:** https://man7.org/linux/man-pages/man1/df.1.html

---

### `du -sh /* 2>/dev/null | sort -rh | head -20`
Finds which top-level directories consume the most space.

- `du -sh` — summarize size of each path
- `sort -rh` — sort by size descending, human-readable
- `head -20` — show top 20 results

```bash
du -sh /* 2>/dev/null | sort -rh | head -20
```

**Docs:** https://man7.org/linux/man-pages/man1/du.1.html

---

## 2. Docker cleanup

### `docker system df`
Shows Docker's total disk usage broken down by images, containers, volumes, and build cache. The `RECLAIMABLE` column shows how much can be safely freed.

```bash
docker system df
```

**Docs:** https://docs.docker.com/reference/cli/docker/system/df/

---

### `docker system prune -f`
Removes stopped containers, dangling images (untagged), unused networks, and build cache. Does **not** remove named images or volumes.

```bash
docker system prune -f
```

**Docs:** https://docs.docker.com/reference/cli/docker/system/prune/

---

### `docker image prune -a -f`
Removes **all** images not referenced by at least one running container (including tagged images). Use when `docker system prune` isn't enough.

```bash
docker image prune -a -f
```

> Safe to run — Docker will re-pull images on next `docker compose up`.

**Docs:** https://docs.docker.com/reference/cli/docker/image/prune/

---

### Inspect container log sizes

Docker JSON logs can grow unbounded by default and silently fill the disk.

```bash
du -sh /var/lib/docker/containers/*/*-json.log 2>/dev/null | sort -rh | head -10
```

Truncate all logs immediately (non-destructive to running containers):

```bash
truncate -s 0 /var/lib/docker/containers/*/*-json.log
```

---

### Configure log rotation (permanent fix)

Add to `/etc/docker/daemon.json`:

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "50m",
    "max-file": "3"
  }
}
```

Apply without full restart:

```bash
sudo systemctl reload docker
```

This limits each container to 3 × 50 MB = 150 MB of logs maximum.

**Docs:** https://docs.docker.com/config/containers/logging/json-file/

---

## 3. LVM volume expansion

Applicable when the physical disk has unallocated space not yet assigned to the LVM logical volume.

### `lsblk`
Shows block devices, partitions, and LVM volumes as a tree. Reveals the physical disk size vs. what is partitioned.

```bash
lsblk
```

**Docs:** https://man7.org/linux/man-pages/man8/lsblk.8.html

---

### `sudo vgdisplay <vg-name>`
Shows the Volume Group status. Key fields:
- `VG Size` — total physical size available to the VG
- `Alloc PE / Size` — already allocated
- `Free PE / Size` — free space that can be assigned to a logical volume

```bash
sudo vgdisplay ubuntu-vg
```

**Docs:** https://man7.org/linux/man-pages/man8/vgdisplay.8.html

---

### `sudo lvextend -l +100%FREE <lv-path>`
Extends the logical volume to use all remaining free space in the volume group. `-l +100%FREE` means "add all free extents".

```bash
sudo lvextend -l +100%FREE /dev/ubuntu-vg/ubuntu-lv
```

**Docs:** https://man7.org/linux/man-pages/man8/lvextend.8.html

---

### `sudo resize2fs <device>`
Expands the ext4 filesystem to fill the newly enlarged logical volume. Can be done **online** (no downtime required).

```bash
sudo resize2fs /dev/mapper/ubuntu--vg-ubuntu--lv
```

**Docs:** https://man7.org/linux/man-pages/man8/resize2fs.8.html

---

## Full procedure summary

```bash
# 1. Check overall disk usage
df -h

# 2. Find what is consuming space
du -sh /* 2>/dev/null | sort -rh | head -20

# 3. Check Docker disk usage
docker system df

# 4. Remove unused Docker images
docker image prune -a -f

# 5. Truncate container logs if needed
truncate -s 0 /var/lib/docker/containers/*/*-json.log

# 6. Verify free space recovered
df -h /

# --- If disk still needs to grow (LVM) ---

# 7. Confirm unallocated space exists
lsblk
sudo vgdisplay ubuntu-vg

# 8. Extend logical volume to max
sudo lvextend -l +100%FREE /dev/ubuntu-vg/ubuntu-lv

# 9. Expand filesystem online
sudo resize2fs /dev/mapper/ubuntu--vg-ubuntu--lv

# 10. Confirm
df -h /
```