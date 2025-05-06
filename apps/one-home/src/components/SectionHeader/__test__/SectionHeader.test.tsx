import React from 'react';
import { customRender, screen } from '@apps/one-home/src/services/test/test-utils';
import SectionHeader from '../SectionHeader';
import { CalendarIcon, Button } from '@razorpay/blade/components';

describe('OneHome SectionHeader', () => {
  it('renders SectionHeader.Title correctly', () => {
    customRender(<SectionHeader.Title>Title</SectionHeader.Title>);
    expect(screen.getByText('Title')).toBeInTheDocument();
  });

  it('renders SectionHeader.Badges correctly', () => {
    customRender(
      <SectionHeader.Badges>
        <SectionHeader.Badge text="Badge" icon={CalendarIcon} />
      </SectionHeader.Badges>,
    );
    expect(screen.getByText('Badge')).toBeInTheDocument();
  });

  it('renders SectionHeader.Actions correctly', () => {
    customRender(
      <SectionHeader.Actions>
        <Button
          variant="secondary"
          color="primary"
          size="medium"
          iconPosition="right"
          display={{ base: 'none', s: 'none', m: 'block', l: 'block' }}
        >
          Test button text
        </Button>
      </SectionHeader.Actions>,
    );
    expect(screen.getByText(/Test button text/i)).toBeInTheDocument();
  });
});
