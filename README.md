# DnDSounds

A simple protected soundboard for hosting on InfinityFree with a shared password login.

## Structure

- `index.php` renders the login screen and the soundboard.
- `data/sounds.php` contains the list of sound fragments and metadata.
- `assets/sounds/` stores the audio files.
- `src/Auth.php` provides the password verification logic.

## Adding sound fragments

1. Place each audio file in `assets/sounds/`.
   - Keep `knowledge.mp3` in the root of `assets/sounds/`.
   - For all other sounds, create a subfolder under `assets/sounds/` named after the `occasion` value and place the file there.
2. Add a new entry in `data/sounds.php` with:
   - `title`
   - `character`
   - `occasion`
   - `file` (relative path from the web root, for example `assets/sounds/dragon-roar.mp3`)
   - `description`
3. Keep file names lowercase and use hyphen separators to avoid issues on free hosting.

## InfinityFree deployment

- Upload the repository contents to the public web root on InfinityFree.
- Create a production `env.php` or equivalent environment file if you need a custom password.
- The current app reads the password hash from `APP_PASSWORD_HASH` in the environment; set it to a password hash generated with `password_hash()`.
