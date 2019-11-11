function SettlementOverview(props) {
  return (
    <div>
      <div>setl_7dqkj1nBSEZqo</div>
      <div class="settlement-detail-row">
        <span>Settlement Amount</span>
        <span>10,745 INR</span>
      </div>
      <div class="settlement-detail-row">
        <span>Total Fee</span>
        <span>260 INR</span>
      </div>
      <div class="settlement-detail-row settlement-sub-row">
        <span>Razorpay Fee</span>
        <span>220 INR</span>
      </div>
      <div class="settlement-detail-row settlement-sub-row">
        <span>GST(18%) Fee</span>
        <span>40 INR</span>
      </div>
      <div class="settlement-payment-info">
        <i class="i i-info-circle" />
        <span>View your payment methods</span>
      </div>
    </div>
  );
}

export default SettlementOverview;
