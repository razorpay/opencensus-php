export const downtime_mock_response = {
  status_code: 200,
  success: true,
  data: [
    {
      id: 'down_L23yF0CdNQdVL6',
      entity: 'payment.downtime',
      method: 'card',
      begin: 1673263881,
      end: null,
      status: 'started',
      scheduled: false,
      severity: 'high',
      instrument: { network: 'VISA' },
      created_at: 1673263884,
      updated_at: 1673263884,
    },
    {
      id: 'down_L23yF0CdNQdVL6',
      entity: 'payment.downtime',
      method: 'card',
      begin: 1673263881,
      end: null,
      status: 'started',
      scheduled: false,
      severity: 'medium',
      instrument: { network: 'RUPAY' },
      created_at: 1673263884,
      updated_at: 1673263884,
    },
    {
      id: 'down_L23yF0CdNQdVL6',
      entity: 'payment.downtime',
      method: 'upi',
      begin: 1673263881,
      end: null,
      status: 'started',
      scheduled: false,
      severity: 'medium',
      instrument: { vpa_handle: 'okaxis' },
      created_at: 1673263884,
      updated_at: 1673263884,
    },
  ],
};

export const high_sev_downtime_mock = {
  id: 'down_L23yF0CdNQdVL6',
  entity: 'payment.downtime',
  method: 'card',
  begin: 1673263881,
  end: null,
  status: 'started',
  scheduled: false,
  severity: 'high',
  instrument: { network: 'VISA' },
  created_at: 1673263884,
  updated_at: 1673263884,
};

export const downtime_mock_response_with_no_downtime = {
  status_code: 200,
  success: true,
  data: [],
};

export const failed_downtime_response = {
  status_code: 500,
  success: false,
  data: [],
};

export const previous_downtimes_mock = {
  status_code: 200,
  success: true,
  data: [
    {
      id: 'down_L23yF0CdNQdVL6',
      entity: 'payment.downtime',
      method: 'card',
      begin: 1675253787,
      end: 1675253907,
      status: 'started',
      scheduled: false,
      severity: 'high',
      instrument: { network: 'VISA' },
      created_at: 1675253787,
      updated_at: 1675253787,
    },
    {
      id: 'down_L23yF0CdNQdVL6',
      entity: 'payment.downtime',
      method: 'netbanking',
      begin: 1675426707,
      end: 1675426947,
      status: 'started',
      scheduled: false,
      severity: 'high',
      instrument: { bank: 'ICIC' },
      created_at: 1675426707,
      updated_at: 1675426707,
    },
  ],
};

export const failed_sr_mock_response = {
  status_code: 200,
  success: true,
  data: {
    Code: 'SERVER_ERROR',
    Description: 'The server encountered an error. The incident has been reported to admins.',
  },
};

export const sr_mock_response = {
  status_code: 200,
  success: true,
  data: {
    sr: 30,
    total: 34005,
    successful: 1002,
  },
};
