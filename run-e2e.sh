#!/bin/sh

yarn test:e2e --grep '@flow=auth' --project 'chromium'
yarn test:e2e --grep-invert '@flow=auth'
