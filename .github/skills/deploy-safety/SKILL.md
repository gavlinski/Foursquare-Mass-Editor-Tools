# Deploy Safety Skill

## Name
Deployment Safety & Server State Management

## Description
Documenta práticas de segurança durante deployment, especialmente tratamento de dirty-state (arquivos não-comitados), backup automático e recuperação segura.

## When to use
Carregue esta skill quando:
- Debugar falhas de deploy
- Implementar novos safety mechanisms
- Investigar server state inconsistencies
- Melhorar backup/restore procedures
- Trabalhar com deploy.sh ou CI/CD pipeline

## Key Files
- `deploy.sh` - Deploy script com dirty-state handling
- `docker-entrypoint.sh` - Container startup com ServerName config
- `.github/workflows/deploy.yml` - CI/CD pipeline
- `docs/BUILD_AND_DEPLOY.md` - Documentação

## Problem: Dirty Repository State

### Scenario
```
Developer edits file X on server (e.g., apache-config-production.conf)
Next deploy runs: git pull fails
Error: "Your local changes would be overwritten by merge"
Deploy blocked entirely
```

### Root Cause
```
Local server changes → git pull blocked → Manual recovery needed
Takes 20+ minutes to fix
Server offline during troubleshooting
```

## Solution: Automatic Dirty-State Handling

### Mechanism in deploy.sh

**Step 1: Detect dirty state**
```bash
# Before pull, check if repo has uncommitted changes
git status --porcelain > /tmp/pre_sync_status.txt
git diff > /tmp/pre_sync_diff.txt
git diff --cached > /tmp/pre_sync_staged.txt

# If any output = repo is dirty
if [ -s /tmp/pre_sync_status.txt ]; then
    echo "⚠️ Server repo is dirty (uncommitted changes)"
fi
```

**Step 2: Backup changes**
```bash
# Save current state for recovery
backup_dir="/var/backups/4sqmet/pre_sync_$(date +%s)"
mkdir -p $backup_dir
cp /tmp/pre_sync_* $backup_dir/
```

**Step 3: Automatic stash**
```bash
# Move uncommitted changes to stash (non-destructive)
git stash push -m "pre-sync-backup-$(date +%s)"
# Result: git is now clean, can pull
```

**Step 4: Pull & Continue**
```bash
# Now safe to pull
git pull origin refactor-ia
# Deploy continues normally
```

### Backup Location
```
/var/backups/4sqmet/pre_sync_20260317_151149/
├── pre_sync_status.txt    # git status output
├── pre_sync_diff.txt      # Uncommitted changes
└── pre_sync_staged.txt    # Staged changes
```

### Recovery if Needed
```bash
# List available stashes
git stash list
# Output: stash@{0}: pre-sync-backup-1710681109

# Restore if needed
git stash pop stash@{0}

# Or manually review diff
cat /var/backups/4sqmet/pre_sync_20260317_151149/pre_sync_diff.txt
```

## Real-World Scenario: Apache Config Updates

### Common Case: Live HTTPS Config Update

**Production server has running SSL certificates:**
```
/etc/letsencrypt/live/4sq.eliotools.site/
├── cert.pem
├── key.pem
└── chain.pem
```

**Developer updates apache-config-production.conf in repo:**
```
- Old config: references old cert path
- Live server: using new cert path (configured manually)
- Next deploy: git pull tries to overwrite live config
- Risk: SSL cert path breaks, HTTPS fails
```

### Safe Handling

**Before (would fail):**
```
$ git pull origin refactor-ia
error: Your local changes to 'apache-config-production.conf' 
       would be overwritten by merge
```

**After (automatic safe sync):**
```
$ ./deploy.sh
[1] Detecting server state...
[2] ⚠️ Found uncommitted changes (apache-config-production.conf)
[3] Backing up to /var/backups/4sqmet/pre_sync_*/
[4] Stashing changes (preserving server config)
[5] git pull origin refactor-ia ✓
[6] Apache verification: OK
[7] Deploy continues...
[RESULT] SSL working, config preserved in stash
```

## ServerName Configuration

### Problem: Apache AH00558 Warning

**On container startup:**
```
[core:warn] [pid 1:tid 139...] AH00558: apache2: Could not 
determine the server's fully qualified domain name, using 
127.0.0.1. Set the 'ServerName' directive globally.
```

**Root cause**: Apache requires global `ServerName` directive

### Solution: Dynamic ServerName

**In docker-entrypoint.sh:**
```bash
# Extract FQDN from APP_URL
if [ -z "$SERVER_NAME" ] && [ -n "$APP_URL" ]; then
    SERVER_NAME=$(printf '%s\n' "$APP_URL" | sed -E 's#^[a-zA-Z]+://([^/:]+).*#\1#')
    # Example: https://4sq.eliotools.site → 4sq.eliotools.site
fi

# Write to Apache global config
cat >> /etc/apache2/apache2.conf << EOF
# Global ServerName (auto-generated)
ServerName ${SERVER_NAME:-localhost}
EOF
```

**Result**: No more AH00558 warning on container start

## Backup & Restore

### Before Deploy: Full Backup

```bash
# Create timestamped backup
tar -czf /var/backups/4sqmet/backup_$(date +%s).tar.gz \
    /var/www/4sqmet/ \
    /etc/apache2/ \
    --exclude=node_modules \
    --exclude=vendor
```

### Manual Restore if Needed

```bash
# List available backups
ls -lht /var/backups/4sqmet/backup_*.tar.gz

# Restore latest
LATEST=$(ls -t /var/backups/4sqmet/backup_*.tar.gz | head -n1)
cd /var/www
tar -xzf $LATEST

# Restart services
docker restart 4sqmet
# or
systemctl restart apache2
```

## Integration with CI/CD

### GitHub Actions: Download & Apply

**In workflow before deploy.sh:**
```yaml
- name: 📤 Download build artifacts
  uses: actions/download-artifact@v4
  with:
    name: minified-assets
    path: .

- name: 🚀 Deploy to server
  run: |
    # Copy build artifacts first
    scp build-info.json ${DEPLOY_USER}@${DEPLOY_HOST}:/var/www/4sqmet/
    scp js/*.min.js ${DEPLOY_USER}@${DEPLOY_HOST}:/var/www/4sqmet/js/
    
    # Then run deploy.sh (handles dirty state)
    bash deploy.sh
```

## Health Check Pattern

```bash
# After deploy completes
echo "🔍 Checking site health..."
curl -I https://4sq.eliotools.site

# Expected: 200 or 302 (redirect to app)
# If fails: check logs
docker logs 4sqmet | tail -50
```

## Best Practices

### ✅ DO

1. **Always verify server state before pulling**
   ```bash
   git status      # Check for local changes
   git log --oneline -5  # Verify commit history
   ```

2. **Keep backups for minimum 30 days**
   ```bash
   # Cleanup old backups monthly
   find /var/backups/4sqmet -name "backup_*.tar.gz" -mtime +30 -delete
   ```

3. **Test deploy on staging first**
   ```bash
   # Before production
   ./deploy.sh staging
   ```

4. **Document server customizations**
   ```bash
   # Record any manual edits on server
   echo "SSL config updated manually" >> /var/backups/4sqmet/NOTES.txt
   ```

5. **Use deploy.sh for important files**
   ```bash
   # Never SSH-edit critical files directly
   # Always commit and deploy
   ```

### ❌ DON'T

1. **Never skip dirty-state checks**
   ```bash
   ❌ git pull --force  # Loses server changes
   ✅ Use deploy.sh (handles automatically)
   ```

2. **Don't ignore stash warnings**
   ```bash
   ⚠️ If deployment logs show "Stashed changes for preservation"
   → Investigate why server had local changes
   → Review stash content before pulling again
   ```

3. **Don't delete backups immediately**
   ```bash
   ❌ tar -xzf backup.tar.gz && rm backup.tar.gz
   ✅ Keep for 30+ days before cleanup
   ```

4. **Don't mix manual edits with deploy**
   ```bash
   ❌ Edit file → Run deploy → Lose changes
   ✅ Commit changes → Deploy
   ```

## Troubleshooting

### Deploy fails: "Local changes would be overwritten"

**Cause**: Automatic stash failed (permissions issue)

**Debug:**
```bash
ssh root@server
cd /var/www/4sqmet
git status  # Check what's dirty
git stash list  # Check stash history
```

**Fix:**
```bash
# Manual stash
git stash push -m "manual-backup-$(date +%s)"

# Then retry deploy
./deploy.sh
```

### Stash accumulating over time

**Cause**: Multiple deploys without manual cleanup

**Monitor:**
```bash
# Check stash size
du -sh /var/stash
git stash list | wc -l

# Clean old stashes (keep last 10)
for i in $(seq 10 $(git stash list | wc -l)); do
    git stash drop "stash@{$i}"
done
```

### Cannot restore from backup: "Permission denied"

**Cause**: Backup created with wrong permissions

**Fix:**
```bash
# Ensure correct ownership
chown www-data:www-data backup_*.tar.gz
chmod 640 backup_*.tar.gz

# Restore
tar -xzf backup_latest.tar.gz
chown -R www-data:www-data /var/www/4sqmet
```

### ServerName still showing warning

**Cause**: EntryPoint not executed (cached container)

**Fix:**
```bash
# Rebuild container
docker build -t 4sqmet:latest .

# Stop old container
docker stop 4sqmet

# Run new container
docker run -e APP_URL=https://4sq.eliotools.site ...
```

## Safety Checklist

- [ ] Verify `deploy.sh` has dirty-state handling
- [ ] Check `/var/backups/4sqmet/` exists with recent backups
- [ ] Confirm `docker-entrypoint.sh` sets ServerName
- [ ] Test deploy on staging server first
- [ ] Monitor first production deploy closely
- [ ] Document any manual server changes
- [ ] Verify backup retention policy (30+ days)
- [ ] Test manual restore procedure monthly

---

**Última atualização**: 17 de Março de 2026  
**Versão**: 1.0.0  
**Referências**: `deploy.sh`, `docker-entrypoint.sh`, `docs/BUILD_AND_DEPLOY.md`
