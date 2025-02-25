import React from 'react';
import { renderToString } from 'react-dom/server';
import { SHELL_ERROR_TRACE } from '@apps/shell/src/server/utils/error-utils';
import type { Request, Response } from 'express';

const BaseHTML = ({
  helpText,
  info,
  statusCode,
  abstractedCode,
  traceId,
}: {
  statusCode: number;
  helpText?: string;
  info: string;
  abstractedCode?: string;
  traceId: string;
}) => {
  return (
    <html>
      <head>
        <meta charSet="utf-8" />
        <meta name="google" content="notranslate" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="author" content="Razorpay" />
        <link rel="icon" type="image/png" href="https://razorpay.com/favicon.png" />
        <title>Oops! | Razorpay</title>
      </head>
      <body
        style={{
          margin: 0,
        }}
      >
        <div
          style={{
            backgroundSize: 'contain',
            backgroundRepeat: 'left top',
            backgroundPosition: 'center',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            minWidth: 480,
            zIndex: 0,
            position: 'fixed',
            backgroundColor: '#f4f4f4',
            backgroundImage: `url('data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22360%22%20height%3D%22652%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22m372.988%20631.28%2036.7%2019.041c4.11%202.126%208.786-1.268%208.786-6.377l.02-27.545c0-5.554-2.838-10.617-7.292-12.96l-79.529-41.968c-1.979-1.04-4.261-1.052-6.251-.012L260.9%20595.221c-4.837%202.526-10.361-1.463-10.361-7.497l-.001-114.077c0-9.726%204.989-18.561%2012.796-22.641L363%20398.866c4.838-2.526%2010.362%201.463%2010.362%207.498v115.013c0%206.686-6.13%2011.109-11.493%208.297l-24.863-13.063c-3.485-1.829-3.485-7.441-.011-9.27l70.642-37.259c6.625-3.498%2010.857-11.007%2010.857-19.282l-.031-310.189c0-4.526-4.15-7.52-7.776-5.612l-32.7%2017.258c-3.181%201.681-6.857%201.681-10.049.012L247.287%2089.065c-3.949-2.069-3.959-8.423-.011-10.515l74.157-39.11c3.211-1.692%206.877.96%206.877%204.96v48.22c0%209.703-4.978%2018.527-12.755%2022.618l-114.178%2060.004c-4.312%202.263-9.241-1.292-9.241-6.675V65.384c0-3.28-1.676-6.275-4.312-7.658l-34.043-17.978c-3.151-1.657-6.757.937-6.757%204.869v23.819c0%209.726-4.988%2018.56-12.795%2022.64l-79.464%2041.517c-4.837%202.526-10.361-1.462-10.361-7.497V11.021c0-9.726%204.988-18.561%2012.795-22.641l79.484-41.506c4.837-2.526%2010.362%201.463%2010.362%207.498v25.659c0%205.154%202.635%209.829%206.756%2012.012l60.468%2031.693c3.777%202%208.11-1.12%208.11-5.84v-124.659M187.842-121%20309.15-56.985c2.474%201.303%202.474%205.292%200%206.595l-66.448%2036.055c-2.151%201.132-2.151%204.583%200%205.715l33.993%2017.932c3.625%201.92%207.826%201.92%2011.452%200l129.68-68.438-90.274-47.637L107.507%208.844c-3.757%201.977-6.16%206.229-6.16%2010.915v223.269c0%204.412%202.262%208.435%205.797%2010.298l34.134%2018.013c2.686%201.417%205.767-.8%205.767-4.161l-.021-72.518c0-7.555-6.938-12.561-12.987-9.361l-159.29%2084.074c-3.858%202.034-8.271-1.154-8.271-5.966V142.874c0-5.303%204.867-8.812%209.12-6.572l70.116%2036.974c6.756%203.566%2011.068%2011.235%2011.068%2019.658v70.553c0%206.629-3.393%2012.652-8.705%2015.452l-71.51%2037.739c-3.838%202.023-3.828%208.195.01%2010.218L92.54%20387.665c3.948%202.069%203.959%208.424.01%2010.515l-74.157%2039.111c-3.211%201.692-6.877-.96-6.877-4.96v-71.079c0-9.703%204.979-18.526%2012.755-22.618l114.178-60.003c4.313-2.263%209.241%201.291%209.241%206.674v126.042c0%203.28%201.676%206.274%204.312%207.657l34.044%2017.979c3.151%201.657%206.756-.938%206.756-4.869v-23.819c0-9.726%204.989-18.561%2012.795-22.641l112.28-58.758c4.838-2.526%2010.362%201.463%2010.362%207.498v114.075c0%209.726-4.989%2018.561-12.796%2022.641l-112.28%2058.759c-4.837%202.526-10.361-1.463-10.361-7.498v-25.659c0-5.155-2.636-9.829-6.756-12.012l-76.076-40.197c-3.777-2-8.109%201.12-8.109%205.84v124.66c0%205.166%202.656%209.863%206.797%2012.035l76.085%2039.819c3.777%201.978%208.089-1.143%208.089-5.851v-27.728c0-5.977-3.06-11.406-7.847-13.932L63.678%20515.331c-2.474-1.303-2.474-5.292%200-6.595l33.468-17.659c2.15-1.131%202.15-4.583%200-5.715L63.153%20467.43c-3.626-1.92-7.827-1.92-11.452%200L-78%20535.823l90.273%2047.638L232.32%20467.876c3.757-1.978%206.16-6.229%206.16-10.915v-223.27c0-4.412-2.262-8.435-5.796-10.298l-34.135-18.012c-2.686-1.418-5.766.8-5.766%204.16l.02%2072.518c0%207.555%206.938%2012.561%2012.987%209.361l159.291-84.062c3.857-2.035%208.271%201.154%208.271%205.966v120.533c0%205.303-4.868%208.811-9.12%206.571l-70.117-36.973c-6.756-3.566-11.068-11.235-11.068-19.658v-70.553c0-6.629%203.393-12.652%208.705-15.452l69.713-36.791%22%20stroke%3D%22url%28%23a%29%22%20stroke-miterlimit%3D%2210%22%2F%3E%3Cdefs%3E%3ClinearGradient%20id%3D%22a%22%20x1%3D%22189.51%22%20y1%3D%22-18.785%22%20x2%3D%22189.51%22%20y2%3D%22420.978%22%20gradientUnits%3D%22userSpaceOnUse%22%3E%3Cstop%20stop-color%3D%22%23053CB9%22%2F%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%230444C5%22%20stop-opacity%3D%220%22%2F%3E%3C%2FlinearGradient%3E%3C%2Fdefs%3E%3Cscript%20xmlns%3D%22%22%2F%3E%3C%2Fsvg%3E')`,
          }}
        />
        <img
          style={{
            position: 'absolute',
            top: '10px',
            left: '10px',
            height: '20px',
            backgroundColor: '#f4f4f4',
            objectFit: 'contain',
            zIndex: 1,
          }}
          alt="Razorpay Logo"
          src="https://cdn.razorpay.com/logo-small.png"
        />
        <div
          style={{
            display: 'flex',
            alignItems: 'flex-end',
            justifyContent: 'center',
            width: '100%',
            height: '100vh',
            position: 'relative',
            textAlign: 'center',
            zIndex: 1,
            fontFamily:
              '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
          }}
        >
          <div
            style={{
              maxWidth: '600px',
              padding: '16px',
              marginBottom: 50,
            }}
          >
            <h1
              style={{
                fontSize: '120px',
                fontWeight: 'bold',
                color: '#333',
              }}
            >
              {abstractedCode || statusCode}
            </h1>
            <p
              style={{
                fontSize: '20px',
                color: '#666',
                fontWeight: 'bold',
                margin: 0,
              }}
            >
              {info}
            </p>
            <small
              style={{
                maxWidth: '200px',
                fontSize: '12px',
                marginTop: '10px',
                color: '#666',
              }}
            >
              {helpText}
            </small>
          </div>
          <small
            style={{
              fontSize: '10px',
              color: '#666',
              position: 'fixed',
              right: 10,
              bottom: 10,
              fontWeight: 'bold',
            }}
          >
            {traceId}
          </small>
        </div>
      </body>
    </html>
  );
};

const ErrorPage = ({ error, traceId }: any) => {
  const statusCode = error?.statusCode || 500;

  // 401 is unique, if occured, will be redirected to PHP
  const errorMessages: Record<
    number | 'default',
    { info: string; helpText: string; abstractedCode?: string }
  > = {
    404: {
      info: SHELL_ERROR_TRACE.PAGE_NOT_FOUND,
      helpText: 'We couldn’t locate the page you’re looking for.',
    },
    // Mostly 500 if internal, also for other case
    default: {
      abstractedCode: 'Uh oh!',
      info: 'Our sincere apologies for the inconvenience.',
      helpText:
        'We’re addressing the issue with the highest priority and appreciate your patience.',
    },
  };

  // Attach req id
  const { info, helpText, abstractedCode } = errorMessages?.[statusCode] || errorMessages.default;

  return (
    <BaseHTML
      statusCode={statusCode}
      info={info}
      helpText={helpText}
      abstractedCode={abstractedCode}
      traceId={traceId}
    />
  );
};

export const renderErrorPage = (req: Request, res: Response, error: any) => {
  const appHtml = renderToString(<ErrorPage error={error} traceId={req.x_shell_request_id} />);

  const html = `<!DOCTYPE html>${appHtml}`;

  return res.status(error?.statusCode || 500).send(html);
};
