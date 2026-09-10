#!/usr/bin/env bash

# Backwards-compatible entry point for building an uploadable plugin ZIP.
exec "$(dirname "$0")/scripts/build-release.sh" "$@"
