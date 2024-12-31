const getBillUrl = (id: string | null) =>
  `${process.env.UNIVERSE_PUBLIC_BILLME_BILL_BASE_URL}/${id}`;

export default getBillUrl;
