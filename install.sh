#!/bin/sh

BASEDIR=$(dirname $(dirname $(dirname $(pwd))))
VENDOR_DIR=$(basename $(dirname $(dirname $(pwd))))
DIRNAME=mtproto-proxy
FILES_DIR="opt/${DIRNAME}"
CD=$(pwd -P)

cd "${BASEDIR}"
mv "${CD}/${FILES_DIR}" "${BASEDIR}/"
mv "$VENDOR_DIR" "${BASEDIR}/${DIRNAME}"
