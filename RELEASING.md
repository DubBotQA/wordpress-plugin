# Testing and publishing the DubBot WordPress plugin

## One-command compatibility smoke test

Install Docker Desktop, start it, then run this from the plugin repository:

```sh
./scripts/test-plugin.sh
```

The command starts an isolated WordPress 7.1 stack at <http://localhost:8088>, installs and activates the checked-out plugin, and uses a local mock DubBot API. It verifies that the plugin loads, creates its URLs correctly, reads API metadata, and registers/localizes its browser scripts. No real embed key, customer site, or DubBot API traffic is involved.

After the automated check, sign in at <http://localhost:8088/wp-admin/> with `admin` / `admin`, open the generated **DubBot smoke test** page in the editor, and do a quick visual check of the button/modal. The test data stays in Docker volumes so the site is available for debugging. Remove it when finished:

```sh
./scripts/test-plugin.sh --clean
```

To test a different supported WordPress release, set `WORDPRESS_VERSION` before running the command. For example:

```sh
WORDPRESS_VERSION=6.8-php8.3-apache ./scripts/test-plugin.sh
```

## Release checklist

1. Update **both** `Version:` in `dubbot.php` and `Stable tag:` in `readme.txt` to a new, matching version. Update `Tested up to:` only after the Docker test and manual editor check pass. Keep the changelog and upgrade notice current.
2. Build the exact uploadable payload:

   ```sh
   ./scripts/build-release.sh
   ```

   It checks PHP syntax, fails on whitespace errors or version mismatches, and writes `dist/dubbot-VERSION.zip`. `plugin-files.txt` is the single source of truth for files delivered in the ZIP and to WordPress.org.
3. Commit and push the source change in Git through the usual review process.
4. Preview the WordPress.org SVN update (this is read-only; it does not modify the checkout):

   ```sh
   ./scripts/deploy-wporg.sh
   ```

   The default checkout is the sibling `../dubbot-wordpress-svn`. Use `--svn-dir` or `WPORG_SVN_DIR` if it lives elsewhere. The script verifies that it is the `dubbot` WordPress.org repository and refuses a dirty checkout or an existing tag.
5. Inspect the displayed SVN changes. The script synchronizes the release payload to `trunk/` and creates `tags/VERSION/`; this is the WordPress.org release mechanism. When satisfied, publish with the configured WordPress.org SVN credentials:

   ```sh
   ./scripts/deploy-wporg.sh --commit
   ```

   WordPress.org builds the public ZIP from the stable tag after the SVN commit. If the plugin uses WordPress.org Release Confirmation, confirm the emailed release request in the Release Management dashboard as well. Do not manually upload `dist/dubbot-VERSION.zip` to WordPress.org; it is for local/manual installation checks.
