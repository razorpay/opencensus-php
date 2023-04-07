import CreditSettlement from './Views/CreditSettlement';
import CurrentSettlementCycle from './Views/CurrentSettlementCycle';
import InstantSettlement from './Views/InstantSettlement';
import PricingPlan from './Views/PricingPlan';
import ReduceSettlementCycle from './Views/ReduceSettlementCycle';
import SettlementBreakup from './Views/SettlementBreakup';
import SettlementCheck from './Views/SettlementCheck';
import SettlementCycle from './Views/SettlementCycle';
import StatusCheck from './Views/StatusCheck';

export const Queries = [
  {
    query: 'How to check status of my settlement ID/settlements?',
    Component: StatusCheck,
  },
  {
    query: 'How to check settlement status of my payment ID?',
    Component: SettlementCheck,
  },
  {
    query: 'What is the settlement cycle Razorpay offers?',
    Component: SettlementCycle,
  },
  {
    query: 'What is my current settlement cycle?',
    Component: CurrentSettlementCycle,
  },
  {
    query: 'How to reduce my settlement cycle?',
    Component: ReduceSettlementCycle,
  },
  {
    query: 'How do I enable instant settlement?',
    Component: InstantSettlement,
  },
  {
    query: 'Why is the settlement break-up?',
    Component: SettlementBreakup,
  },
  {
    query: 'What is the pricing plan for my transactions?',
    Component: PricingPlan,
  },
  {
    query: 'How to know if settlements are credited to my bank account?',
    Component: CreditSettlement,
  },
];
