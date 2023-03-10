#!/bin/sh

npm run test:e2e -- -- --grep '@flow=auth' --project 'chromium'
npm run test:e2e -- -- --grep-invert '@flow=auth'
