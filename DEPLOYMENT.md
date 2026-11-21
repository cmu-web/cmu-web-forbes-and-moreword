# Deployment

## Release Files

Each version tag (e.g., `0.1.4`) has a GitHub release with:

| File                      | What                              |
| ------------------------- | --------------------------------- |
| `forbes-and-moreword.zip` | The theme, to upload to WordPress |

The same tag publishes the docs site as a container image,
`ghcr.io/cmu-web/cmu-web-forbes-and-moreword`.

## Install

1. Download `forbes-and-moreword.zip` from the
   [latest release](https://github.com/cmu-web/cmu-web-forbes-and-moreword/releases/latest/download/forbes-and-moreword.zip).
2. In WordPress, select **Appearance › Themes › Add New Theme › Upload
   Theme**, choose the file and select **Install Now**.
3. Select **Activate**.

It works on WordPress 6.6 or later, with PHP 8.1 or later. Visitors'
browsers load CMU's template from www.cmu.edu and the kit from jsDelivr.

### Update

Upload the new release's zip the same way; WordPress offers to replace the
installed theme.

## Run the Docs Site

The docs site is one image, built by the `Dockerfile`: WordPress with Apache,
the theme and the site, seeded into a SQLite database when the image is
built. The container runs with a read-only root filesystem as `www-data`; at
start it copies the database into `/tmp`, so a restart puts the site back as
built. It answers 404 for the admin, sign-in, XML-RPC and REST paths. To try
it:

```sh
docker build -t forbes-and-moreword .
docker run --rm --read-only --tmpfs /tmp --cap-drop=ALL \
  --security-opt no-new-privileges -p 127.0.0.1:3804:8080 \
  -e FAM_URL=http://127.0.0.1:3804 forbes-and-moreword
docker/check.sh http://127.0.0.1:3804
```

Podman takes the same flags.
