import { PRODUCTS_DATA } from 'merchant/components/SidebarV2/utils/Products';
import {
  SearchableEntitiesType,
  entityAttributesTypes,
  statusKeywordsStoreType,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';
const { transactions, settlements, payment_links, payment_pages, payment_button } = PRODUCTS_DATA;

export const searchableEntities: SearchableEntitiesType = {
  Payments: {
    id: 'Payments',
    route: '/payments',
    icon: transactions.icon,
    attributes: {
      PaymentId: 'id',
      OrderId: 'order_id',
      Email: 'email',
      PhoneNumber: 'contact',
      PaymentStatus: 'status',
    },
  },
  Settlements: {
    id: 'Settlements',
    route: '/settlements',
    icon: settlements.icon,
    attributes: {
      SettlementId: 'id',
      SettlementStatus: 'status',
    },
  },
  Refunds: {
    id: 'Refunds',
    route: '/refunds',
    icon: transactions.icon,
    attributes: {
      RefundId: 'id',
      PaymentId: 'payment_id',
      RefundStatus: 'public_status',
    },
  },
  Orders: {
    id: 'Orders',
    route: '/orders',
    icon: transactions.icon,
    attributes: {
      OrderId: 'id',
      OrderStatus: 'status',
    },
  },
  Disputes: {
    id: 'Disputes',
    route: '/disputes',
    icon: transactions.icon,
    attributes: {
      DisputeId: 'id',
      PaymentId: 'payment_id',
      DisputeType: 'phase',
      DisputeState: 'status',
    },
  },
  Invoices: {
    id: 'Invoices',
    route: '/invoices',
    icon: transactions.icon,
    attributes: {
      InvoiceId: 'id',
    },
  },
  PaymentLinks: {
    id: 'PaymentLinks',
    route: '/paymentlinks',
    icon: payment_links.icon,
    attributes: {
      PaymentLinkId: 'id',
      PaymentLinkUrl: 'short_url',
      PaymentLinkStatus: 'status',
      Email: 'customer_email',
      PhoneNumber: 'customer_contact',
      PaymentLinkBatchId: 'batch_id',
    },
  },
  PaymentPages: {
    id: 'PaymentPages',
    route: '/paymentpages',
    icon: payment_pages.icon,
    attributes: {
      PaymentPageUrl: 'short_url',
      PaymentPageStatus: 'status',
    },
  },
  PaymentButtons: {
    id: 'PaymentButtons',
    route: '/paymentbuttons',
    icon: payment_button.icon,
    attributes: {
      PaymentButtonStatus: 'status',
    },
  },
};

export const entityAttributes: entityAttributesTypes = {
  PaymentId: {
    attributeId: 'PaymentId',
    attributeType: 'entity_id',
    matchWith: /^pay_[a-zA-Z0-9]{0,14}/,
    entities: ['Payments', 'Refunds', 'Disputes'],
  },
  OrderId: {
    attributeId: 'OrderId',
    attributeType: 'entity_id',
    matchWith: /^order_[a-zA-Z0-9]{0,14}/,
    entities: ['Payments', 'Orders'],
  },
  SettlementId: {
    attributeId: 'SettlementId',
    attributeType: 'entity_id',
    matchWith: /^setl_[a-zA-Z0-9]{0,14}/,
    entities: ['Settlements'],
  },
  RefundId: {
    attributeId: 'RefundId',
    attributeType: 'entity_id',
    matchWith: /^rfnd_[a-zA-Z0-9]{0,14}/,
    entities: ['Refunds'],
  },
  DisputeId: {
    attributeId: 'DisputeId',
    attributeType: 'entity_id',
    matchWith: /^disp_[a-zA-Z0-9]{0,14}/,
    entities: ['Disputes'],
  },
  InvoiceId: {
    attributeId: 'InvoiceId',
    attributeType: 'entity_id',
    matchWith: /^inv_[a-zA-Z0-9]{0,14}/,
    entities: ['Invoices'],
  },
  PaymentLinkId: {
    attributeId: 'PaymentLinkId',
    attributeType: 'entity_id',
    matchWith: /^plink_[a-zA-Z0-9]{0,14}/,
    entities: ['PaymentLinks'],
  },
  PaymentLinkUrl: {
    attributeId: 'PaymentLinkUrl',
    attributeType: 'entity_url',
    matchWith: /^https:\/\/rzp\.io\/i\/[a-zA-Z0-9]+$/,
    entities: ['PaymentLinks'],
  },
  PaymentLinkBatchId: {
    attributeId: 'PaymentLinkBatchId',
    attributeType: 'entity_secondary_id',
    matchWith: /^batch_[a-zA-Z0-9]{14}$/,
    entities: ['PaymentLinks'],
  },
  PaymentPageUrl: {
    attributeId: 'PaymentPageUrl',
    attributeType: 'entity_url',
    matchWith: /^https:\/\/rzp\.io\/i\/[a-zA-Z0-9]+$/,
    entities: ['PaymentPages'],
  },
  Email: {
    attributeId: 'Email',
    attributeType: 'entity_email',
    matchWith: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+.[a-zA-Z]{2,}$/,
    entities: ['Payments', 'PaymentLinks'],
  },
  PhoneNumber: {
    attributeId: 'PhoneNumber',
    attributeType: 'entity_contact_number',
    matchWith: /^(?:\+91|0)?[6-9]\d{9}$/,
    entities: ['Payments', 'PaymentLinks'],
  },
  PaymentStatus: {
    attributeId: 'PaymentStatus',
    attributeType: 'entity_state',
    matchWith: ['captured', 'authorized', 'failed', 'refunded', 'authorised'],
    entities: ['Payments'],
  },
  SettlementStatus: {
    attributeId: 'SettlementStatus',
    attributeType: 'entity_state',
    matchWith: ['created', 'processed', 'failed', 'initiated'],
    entities: ['Settlements'],
  },
  RefundStatus: {
    attributeId: 'RefundStatus',
    attributeType: 'entity_state',
    matchWith: ['processed', 'processing', 'paid'],
    entities: ['Refunds'],
  },
  OrderStatus: {
    attributeId: 'OrderStatus',
    attributeType: 'entity_state',
    matchWith: ['created', 'attempted', 'paid'],
    entities: ['Orders'],
  },
  DisputeType: {
    attributeId: 'DisputeType',
    attributeType: 'entity_state',
    matchWith: [
      'retrieval',
      'chargeback',
      'pre_arbitration',
      'pre arbitration',
      'arbitration',
      'fraud',
    ],
    entities: ['Disputes'],
  },
  DisputeState: {
    attributeId: 'DisputeState',
    attributeType: 'entity_state',
    matchWith: ['open', 'lost', 'won', 'closed', 'review', 'under review'],
    entities: ['Disputes'],
  },
  PaymentLinkStatus: {
    attributeId: 'PaymentLinkStatus',
    attributeType: 'entity_state',
    matchWith: ['created', 'partially paid', 'paid', 'cancelled', 'expired'],
    entities: ['PaymentLinks'],
  },
  PaymentPageStatus: {
    attributeId: 'PaymentPageStatus',
    attributeType: 'entity_state',
    matchWith: ['active', 'inactive', 'in active'],
    entities: ['PaymentPages'],
  },
  PaymentButtonStatus: {
    attributeId: 'PaymentButtonStatus',
    attributeType: 'entity_state',
    matchWith: ['active', 'inactive', 'in active'],
    entities: ['PaymentButtons'],
  },
};

export const statusKeywordsStore: statusKeywordsStoreType = {
  Payments: {
    captured: 'captured',
    failed: 'failed',
    refunded: 'refunded',
    authorised: 'authorized',
    authorized: 'authorized',
  },
  Settlements: {
    created: 'created',
    processed: 'processed',
    failed: 'failed',
    initiated: 'initiated',
  },
  Refunds: {
    processed: 'processed',
    processing: 'processing',
    paid: 'paid',
  },
  Orders: {
    created: 'created',
    attempted: 'attempted',
    paid: 'paid',
  },
  Disputes: {
    retrieval: 'retrieval',
    chargeback: 'chargeback',
    arbitration: 'arbitration',
    'pre arbitration': 'pre_arbitration',
    fraud: 'fraud',
    open: 'open',
    lost: 'lost',
    won: 'won',
    closed: 'closed',
    'under review': 'under_review',
    review: 'under_review',
  },
  PaymentLinks: {
    created: 'created',
    paid: 'paid',
    cancelled: 'cancelled',
    expired: 'expired',
    'partially paid': 'partially_paid',
  },
  PaymentPages: {
    active: 'active',
    inactive: 'inactive',
    'in active': 'inactive',
  },
  PaymentButtons: {
    active: 'active',
    inactive: 'inactive',
    'in active': 'inactive',
  },
};
