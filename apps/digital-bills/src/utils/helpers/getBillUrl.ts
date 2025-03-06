const getBillUrl = ({ id, signedToken }: { id: string | null; signedToken?: string }) => {
  const billUrl = `${process.env.UNIVERSE_PUBLIC_BILLME_BILL_BASE_URL}/${id}`;
  if (signedToken) {
    const encodedToken = encodeURIComponent(signedToken);
    return `${billUrl}?signed=${encodedToken}`;
  }
  return billUrl;
};

export default getBillUrl;
