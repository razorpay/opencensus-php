export const user = {
  role: 'owner',
  isAccepted: true,
  isWebsiteSelfServeOn: true,
  business_website: 'http://www.example.com/',
  additional_websites: ['www.google.com', 'www.twitter.com'],
  isAdditionalDomainWhitelistSelfServeOn: true,
  has_key_access: true,
  id: 'LLlV9ud8etevh8',
};

export const initialState = {
  workflows: {
    add_additional_website: {
      loading: false,
      error: null,
      workflow_exists: false,
      needs_clarification: null,
      permission: null,
      request_under_validation: false,
      tags: [],
    },
    additional_website: {
      loading: false,
      error: null,
      workflow_exists: false,
      needs_clarification: null,
      permission: null,
      request_under_validation: false,
      tags: [],
    },
    business_website_automation_status: {
      loading: true,
      data: {},
      error: null,
    },
  },
  session: {
    user,
  },
};

export const initialStateForWorkflows = {
  workflows: {
    add_additional_website: {
      loading: false,
      error: null,
      workflow_exists: false,
      needs_clarification: null,
      permission: null,
      request_under_validation: false,
      tags: [],
    },
    additional_website: {
      workflow_exists: true,
      workflow_status: 'open',
      needs_clarification: 'Website not correct',
      permission: 'update_merchant_website',
      request_under_validation: false,
      tags: ['awaiting-customer-response'],
    },
    business_website_automation_status: {
      loading: true,
      data: {},
      error: null,
    },
  },
  session: {
    user,
  },
};
