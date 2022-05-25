export const BillDeskPoints = () => {
  return (
    <>
      <div>{`<Configurations required on your Billdesk account>`}</div>
      <ol>
        <li>
          Method cards need to be enabled with the merchant requiring networks and S2S/ Seamless
          flow enabled.
        </li>
        <li>
          IP whitelisting is required to make the API calls (Initiate Transaction, Verify
          Transaction/Refund Initiation/Verify Refund).
          <ol type="a">
            <li>52.66.75.174</li>
            <li>52.66.76.63</li>
            <li>52.66.151.218</li>
          </ol>
        </li>
        <li>
          Billdesk S2S card integration supports two features
          <ol type="1">
            <li>Regular card processing</li>
            <li>NoRedirect based Payment</li>
          </ol>
          So the merchant configuration at the BD side should be regular card processing not
          “NoRedirect based Payment”.
        </li>
      </ol>
    </>
  );
};
