# MongoDB Atlas TLS Error - Troubleshooting Guide

The error "TLS handshake failed: tlsv1 alert internal error" typically indicates one of these issues:

## 1. MongoDB Atlas IP Whitelist (Most Common Cause)

MongoDB Atlas blocks connections from unknown IP addresses by default.

### Fix on MongoDB Atlas:

1. Go to https://cloud.mongodb.com/
2. Select your cluster: **cluster0**
3. Click **Network Access** (left sidebar)
4. Click **Add IP Address**
5. Select **Allow Access from Anywhere** (or add `0.0.0.0/0`)
6. Click **Confirm**

⚠️ **Important:** Changes take 1-2 minutes to propagate

### For Production (Recommended):

Instead of "anywhere", whitelist only Render.com's IP ranges:

- Get Render's IPs from: https://render.com/docs/static-outbound-ip-addresses
- Add each IP range individually in MongoDB Atlas Network Access

## 2. Check MongoDB Atlas User Permissions

1. In MongoDB Atlas, go to **Database Access**
2. Verify user: `rousonjamil5328_db_user` exists
3. Ensure it has role: **Atlas Admin** or **Read and Write to any database**
4. Verify password matches (regenerate if unsure)

## 3. Connection String Format

Your connection string should look like:

```
mongodb+srv://<set-in-private-env>
```

✓ Uses `mongodb+srv://` (not `mongodb://`)
✓ Password is URL-encoded (no special characters like @, :, /)
✓ Includes `?appName=Cluster0`

## 4. Test Connection Locally First

Upload `mongodb_test.php` to your server and access it:

```
https://petcaretest-2.onrender.com/mongodb_test.php
```

This will show detailed diagnostics including:

- MongoDB driver version
- OpenSSL version
- Connection attempt results
- Specific error details

## 5. Verify Environment Variable on Render

1. Go to Render Dashboard → Your Service
2. Click **Environment** tab
3. Verify `MONGODB_URI` is set correctly
4. Click **Save Changes** (triggers redeploy)

## 6. Common Mistakes

❌ Forgot to whitelist 0.0.0.0/0 in MongoDB Atlas
❌ Wrong username/password in connection string
❌ Special characters in password not URL-encoded
❌ Using `mongodb://` instead of `mongodb+srv://`
❌ Database user doesn't have proper permissions

## Expected Behavior After Fix

Once MongoDB Atlas IP whitelist is configured:

- ✓ `mongodb_test.php` should show "Successfully connected"
- ✓ Application should load without "MongoDB Connection failed" error
- ✓ Login/Register pages should work

## Still Not Working?

If the issue persists after whitelisting IPs:

1. **Regenerate database user password:**
   - Go to Database Access in MongoDB Atlas
   - Edit the user
   - Set a simple password (no special characters)
   - Update `MONGODB_URI` on Render with new password

2. **Create a new database user:**
   - Create user: `petcare_user`
   - Password: `PetCare2026!` (simple, no URL encoding needed)
   - Role: Atlas Admin
   - Update connection string

3. **Check MongoDB Atlas cluster status:**
   - Ensure cluster is running (not paused)
   - Check for any Atlas service issues

---

**Most likely solution:** Add `0.0.0.0/0` to MongoDB Atlas Network Access whitelist
