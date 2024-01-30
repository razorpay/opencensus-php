export type Order = {
  id: string;
  merchant_id: string;
  reseller_id: string;
  reseller_name: string;
  total_quantity: number;
  total_amount: number;
  net_amount: number;
  status: string;
  delivery_status: string;
  reseller_detail_id: string;
  created_at: number;
  updated_at: number;
};
