import React from "react";
import { Carousel } from '@dashboards/payments/views/PartnerDashboard/Settings/configuration/Carousel';

import { StyledTestimonials } from './styled';

import authorImage1 from 'assets/partner-dashboard/testimonial-author6.png';
import authorImage2 from 'assets/partner-dashboard/testimonial-author5.png';
import authorImage3 from 'assets/partner-dashboard/testimonial-author4.png';

const TestimonialCard = ({ testimonial, authorImage, authorName, authorDescription }) => {
  return (
    <div className="info-card-wrapper">
      <div className="info-card">
        <p>{testimonial}</p>
        <hr className="blue-seperator" />
        <div className="author-wrap">
          <div className="author-img">
            <img src={authorImage} />
          </div>
          <div className="author-info">
            <div className="author-name">{authorName}</div>
            <div className="author-des">{authorDescription}</div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default () => {
  return (
    <StyledTestimonials>
      <Carousel
        hideArrows
        alignDotsLeft
        yellowDots
        carouselItems={[
          <TestimonialCard
            key="1"
            testimonial="Razorpay is scalable and helps us serve diverse range of hotels and deliver a 10% higher transaction success rate. Partnership makes it easier to integrate and get rich data reports."
            authorImage={authorImage2}
            authorName="Sankalp Goel"
            authorDescription="CEO, Djubo"
          />,
          <TestimonialCard
            key="2"
            testimonial="The Razorpay Partner Program helps us deliver reliable solutions for our clients, making us a trustworthy vendor. It’s also rewarding in the form of commissions."
            authorImage={authorImage1}
            authorName="Ramalingam Mageshwaran"
            authorDescription="Sales & Ops Head, Appssea Tech"
          />,
          <TestimonialCard
            key="3"
            testimonial="Partnering with Razorpay made us realize our vision of enabling native payment on WhatsApp Business in India. Now, within chat, one can shop and pay with the method of choice."
            authorImage={authorImage3}
            authorName="Salil Mody"
            authorDescription="Head-Fintech Partnerships, Meta"
          />,
        ]}
      />
    </StyledTestimonials>
  );
};
