import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import GstinAutoPopulate from '../index';
import { cleanup, render } from 'test-utils';

afterEach(() => {
  cleanup();
});

const props = {
  gstin: '29AADCB2230M1ZP',
  errorText: '',
  disabled: false,
  updateGstin: () => {},
  hasGSTIN: false,
  gstinDetails: {
    defaultGstin: '29AADCB2230M1ZP',
    gstinList: ['29AADCB2230M1ZP', '32AADCB2230M1Z2', '27AADCB2230M1ZT', '23AADCB2230M1Z1'],
  },
  location: 'Business Details Tab',
};

test('should render Gstin Autopopulate', () => {
  render(<GstinAutoPopulate {...props} />, {});
});

test('case when gstin is null', () => {
  render(<GstinAutoPopulate {...props} gstin="" />, {});
});

test('case when hasGstin is true', () => {
  render(<GstinAutoPopulate {...props} hasGSTIN />, {});
});
