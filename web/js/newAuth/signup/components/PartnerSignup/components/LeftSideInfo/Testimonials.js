import { Carousel } from 'merchant/views/PartnerDashboard/Settings/configuration/Carousel';

import { StyledTestimonials } from './styled';

import authorImage1 from 'assets/partner-dashboard/testimonial-author1.png';
import authorImage2 from 'assets/partner-dashboard/testimonial-author2.png';
import authorImage3 from 'assets/partner-dashboard/testimonial-author3.png';

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
            testimonial="Easy To Get Services offers Consulting and had a wonderful experience with Razorpay. We were looking for easy to start payment gateway for our customers, most of whom are running their online ecommerce business. Razorpay worked out well for our customers due to easy signup and quick activation of their payment gateway."
            authorImage={authorImage2}
            authorName="Vikas Baruna"
            authorDescription="EasyToGet"
          />,
          <TestimonialCard
            key="2"
            testimonial="Razorpay has been our most recommended payment gateway to all our partners and clients. Their wonderful UX and exceptional support team makes them stand out in the payment gateway industry."
            authorImage={authorImage1}
            authorName="Shruti Vijayvargiya"
            authorDescription="Autuskey Technology Development"
          />,
          <TestimonialCard
            key="3"
            testimonial="The suite of products Razorpay has is just amazing. They are easily integrable, super user-friendly and highly reliable. The partner program has pushed the horizons even further with an amazing support and handhold approach. We now have more power to build secure and scalable solutions for all our merchants. Kudos to the team!"
            authorImage={authorImage3}
            authorName="Shashwat Swaroop"
            authorDescription="Marmeto"
          />,
        ]}
      />
    </StyledTestimonials>
  );
};
