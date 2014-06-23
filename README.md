## DotenvYaml
Recursively merge `.env.yml` and `.env.yml.d/*.yml` into the process environment.  Supports YAML anchors and aliases,
which is the big win over straight JSON for configuration files.

## File: .env.yml
The environment configuration file.  If it's not mentioned here, it won't be included.

## Directory: .env.yml.d/
Configuration partials, parsed in natural sort order.  Should match glob `??-*.yml`, e.g. `00-logging.yml` or
`99-security.yml`.  Build up complex configurations here, then reference them in `.env.yml`.

The `test/.env.yml.d/` directory contains some examples.
