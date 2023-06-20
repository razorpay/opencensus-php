import { PRODUCTS_DATA } from 'merchant/components/SidebarV2/utils/Products';
import {
  SearchableEntitiesType,
  entityAttributesTypes,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';
const { transactions, settlements } = PRODUCTS_DATA;

export const searchableEntities: SearchableEntitiesType = {
  Payments: {
    id: 'Payments',
    route: '/payments',
    icon: transactions.icon,
    attributes: {
      payment_id: 'id',
      order_id: 'order_id',
      email_id: 'email',
      ph_number: 'contact',
      payment_status: 'status',
    },
  },
  Settlements: {
    id: 'Settlements',
    route: '/settlements',
    icon: settlements.icon,
    attributes: {
      settlement_id: 'id',
      settlement_status: 'status',
    },
  },
  Refunds: {
    id: 'Refunds',
    route: '/refunds',
    icon: transactions.icon,
    attributes: {
      refund_id: 'id',
      payment_id: 'payment_id',
      refund_status: 'public_status',
    },
  },
  Orders: {
    id: 'Orders',
    route: '/orders',
    icon: transactions.icon,
    attributes: {
      order_id: 'id',
      order_status: 'status',
    },
  },
  Disputes: {
    id: 'Disputes',
    route: '/disputes',
    icon: transactions.icon,
    attributes: {
      dispute_id: 'id',
      payment_id: 'payment_id',
      dispute_type: 'phase',
      dispute_state: 'status',
    },
  },
};

export const entityAttributes: entityAttributesTypes = {
  PaymentId: {
    attributeId: 'payment_id',
    attributeType: 'entity_id',
    matchWith: /^pay_[a-zA-Z0-9]{0,14}/,
    entities: ['Payments', 'Refunds', 'Disputes'],
  },
  OrderId: {
    attributeId: 'order_id',
    attributeType: 'entity_id',
    matchWith: /^order_[a-zA-Z0-9]{0,14}/,
    entities: ['Payments', 'Orders'],
  },
  SettlementId: {
    attributeId: 'settlement_id',
    attributeType: 'entity_id',
    matchWith: /^setl_[a-zA-Z0-9]{0,14}/,
    entities: ['Settlements'],
  },
  RefundId: {
    attributeId: 'refund_id',
    attributeType: 'entity_id',
    matchWith: /^rfnd_[a-zA-Z0-9]{0,14}/,
    entities: ['Refunds'],
  },
  DisputeId: {
    attributeId: 'dispute_id',
    attributeType: 'entity_id',
    matchWith: /^disp_[a-zA-Z0-9]{0,14}/,
    entities: ['Disputes'],
  },
  Email: {
    attributeId: 'email_id',
    attributeType: 'entity_email',
    matchWith: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+.[a-zA-Z]{2,}$/,
    entities: ['Payments'],
  },
  PhoneNumber: {
    attributeId: 'ph_number',
    attributeType: 'entity_contact_number',
    matchWith: /^(?:\+91|0)?[6-9]\d{9}$/,
    entities: ['Payments'],
  },
  PaymentStatus: {
    attributeId: 'payment_status',
    attributeType: 'entity_state',
    matchWith: ['captured', 'authorized', 'failed', 'refunded'],
    entities: ['Payments'],
  },
  SettlementStatus: {
    attributeId: 'settlement_status',
    attributeType: 'entity_state',
    matchWith: ['created', 'processed', 'failed', 'initiated'],
    entities: ['Settlements'],
  },
  RefundStatus: {
    attributeId: 'refund_status',
    attributeType: 'entity_state',
    matchWith: ['processed', 'processing', 'paid'],
    entities: ['Refunds'],
  },
  OrderStatus: {
    attributeId: 'order_status',
    attributeType: 'entity_state',
    matchWith: ['created', 'attempted', 'paid'],
    entities: ['Orders'],
  },
  DisputeType: {
    attributeId: 'dispute_type',
    attributeType: 'entity_state',
    matchWith: ['retrieval', 'chargeback', 'pre_arbitration', 'arbitration', 'fraud'],
    entities: ['Disputes'],
  },
  DisputeState: {
    attributeId: 'dispute_state',
    attributeType: 'entity_state',
    matchWith: ['open', 'under_review', 'lost', 'won', 'closed'],
    entities: ['Disputes'],
  },
};
