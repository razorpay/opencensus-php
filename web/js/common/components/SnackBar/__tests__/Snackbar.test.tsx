import React from 'react';
import Snackbar from 'common/components/SnackBar/Snackbar';
import { render } from 'test-utils';

describe('<Snackbar />', () => {
  describe('error', () => {
    it('success', () => {
      const { container } = render(
        <Snackbar
          shouldAnimateIn={true}
          icon="success"
          type="success"
          message="success message"
          onClose={() => {}}
          color="positive.900"
        />,
        {},
      );
      expect(container).toMatchSnapshot();
    });
    it('error', () => {
      const { container } = render(
        <Snackbar
          shouldAnimateIn={false}
          icon="failure"
          type="failure"
          message="failure message"
          color="negative.900"
          onClose={() => {}}
        />,
        {},
      );
      expect(container).toMatchSnapshot();
    });
  });

  describe('showSnackbar', () => {
    it('show snackbar', () => {
      const { container } = render(
        <Snackbar
          shouldAnimateIn={false}
          icon="failure"
          type="failure"
          message="failure message"
          color="negative.900"
          onClose={() => {}}
        />,
        {},
      );
      expect(container).toMatchSnapshot();
    });
    it('hide snackbar', () => {
      const { container } = render(
        <Snackbar
          shouldAnimateIn={false}
          icon="failure"
          type="failure"
          message="failure message"
          onClose={() => {}}
          color="negative.900"
        />,
        {},
      );
      expect(container).toMatchSnapshot();
    });
  });
});
