import React from 'react';
import Button from 'common/new-ui/Button';

const faqs = [
  {
    question: 'How much time does it take to view credits on my Razorpay account?',
    answer: 'It takes maximum 2-3 hours for these credits to reflect in your Razorpay account.',
  },
  {
    question: 'What happens if I deposit from a non-verified Razorpay account?',
    answer: 'We will process the refund of the full-amount to non-verified Razorpay account.',
  },
  {
    question: 'What are the limits for IMPS, NEFT, RTGS?',
    answer: 'IMPS upto 5Lakhs, NEFT and RTGS limits depend upon your bank',
  },
];

export default function Questions({ onCloseClick }) {
  return (
    <>
      <ul className="list-group faqs">
        {faqs.map(({ question, answer }, index) => (
          <li className="list-group-item faq" key={index}>
            <div className="question">
              <span className="faq-label">Q:</span>
              {question}
            </div>
            <div className="answer">
              <span className="faq-label">A:</span>
              {answer}
            </div>
          </li>
        ))}
      </ul>
      <div className="Modal__actions">
        <Button.Primary type="button" className="btn btn-primary btn-block" onClick={onCloseClick}>
          Go Back
        </Button.Primary>
      </div>
    </>
  );
}
