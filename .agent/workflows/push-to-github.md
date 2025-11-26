---
description: Upload code changes to GitHub
---

# Push Code to GitHub

This workflow helps you upload your updated code to GitHub easily.

## Steps

1. **Check what files have changed**
```bash
git status
```

2. **Add all your changes**
// turbo
```bash
git add .
```

3. **Commit your changes with a message**
```bash
git commit -m "Your commit message here"
```
> Replace "Your commit message here" with a brief description of what you changed (e.g., "Fixed login bug", "Added new vendor features", etc.)

4. **Push to GitHub**
// turbo
```bash
git push origin main
```

## Quick One-Liner (if you want to do it all at once)
```bash
git add . && git commit -m "Updated code" && git push origin main
```

## Troubleshooting

- If you get authentication errors, you may need to set up a GitHub Personal Access Token
- If push is rejected, try pulling first: `git pull origin main --rebase`
- If you have merge conflicts, resolve them before pushing
