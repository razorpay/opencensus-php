import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';
import { useMutation, useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import Programs from '../index';
import { trackProgramsCardClicked, trackProgramsPageLoadSuccess } from '../events';

jest.mock('@tanstack/react-query', () => ({
  ...jest.requireActual('@tanstack/react-query'),
  useQuery: jest.fn(),
  useMutation: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: jest.fn(),
}));

jest.mock('../events', () => ({
  trackProgramsCardClicked: jest.fn(),
  trackProgramsPageLoadSuccess: jest.fn(),
}));

jest.mock('../hooks/useFetchPrograms', () => ({
  __esModule: true,
  default: jest.fn(),
}));

jest.mock('merchant/views/GCMS/shared/Wrapper', () => ({
  __esModule: true,
  default: ({ children }) => <div>{children}</div>,
}));

describe('<Programs />', () => {
  const mockNavigate = jest.fn();
  const defaultProps = {
    mode: 'test',
    merchantId: 'merchant1',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useNavigate as jest.Mock).mockReturnValue(mockNavigate);
    require('../hooks/useFetchPrograms').default.mockReturnValue({
      isLoading: false,
      programs: {
        items: [],
        total_count: 0,
      },
      next: null,
      prev: null,
      skip: 0,
      count: 0,
      changePageSize: jest.fn(),
      programImages: {},
      isImageLoading: false,
      refetch: jest.fn(),
    });
  });

  test.skip('renders loading state', () => {
    require('../hooks/useFetchPrograms').default.mockReturnValue({
      isLoading: true,
      programs: null,
      next: null,
      prev: null,
      skip: 0,
      count: 0,
      changePageSize: jest.fn(),
      programImages: {},
      isImageLoading: false,
      refetch: jest.fn(),
    });

    render(<Programs {...defaultProps} />);
    expect(screen.getByText('Gift Card Programs')).toBeInTheDocument();
    expect(screen.getByText('loading...')).toBeInTheDocument();
  });

  test('renders empty state when no programs exist', () => {
    render(<Programs {...defaultProps} />);

    expect(screen.getByText('There are no programs yet!!')).toBeInTheDocument();
  });

  test('renders program list when programs exist', () => {
    const mockPrograms = {
      items: [
        { id: 'program1', name: 'Test Program 1', policies:{gift_card_validity_period: 1, gift_card_validity_span: 'day'} },
        { id: 'program2', name: 'Test Program 2', policies:{gift_card_validity_period: 1, gift_card_validity_span: 'day'} },
      ],
      total_count: 2,
    };

    require('../hooks/useFetchPrograms').default.mockReturnValue({
      isLoading: false,
      programs: mockPrograms,
      next: null,
      prev: null,
      skip: 0,
      count: 2,
      changePageSize: jest.fn(),
      programImages: {},
      isImageLoading: false,
      refetch: jest.fn(),
    });

    render(<Programs {...defaultProps} />);

    expect(screen.getByText('Test Program 1')).toBeInTheDocument();
    expect(screen.getByText('Test Program 2')).toBeInTheDocument();
  });

  test.skip('navigates to program details when program card is clicked', async () => {
    const mockPrograms = {
      items: [{ id: 'program1', name: 'Test Program 1' }],
      total_count: 1,
    };

    require('../hooks/useFetchPrograms').default.mockReturnValue({
      isLoading: false,
      programs: mockPrograms,
      next: null,
      prev: null,
      skip: 0,
      count: 1,
      changePageSize: jest.fn(),
      programImages: {},
      isImageLoading: false,
      refetch: jest.fn(),
    });

    render(<Programs {...defaultProps} />);

    const programCard = screen.getByText('Test Program 1');
    await userEvent.click(programCard);

    expect(trackProgramsCardClicked).toHaveBeenCalledWith({
      programId: 'program1',
      programName: 'Test Program 1',
    });
    expect(mockNavigate).toHaveBeenCalledWith('/gcms/programs/program1');
  });
});
