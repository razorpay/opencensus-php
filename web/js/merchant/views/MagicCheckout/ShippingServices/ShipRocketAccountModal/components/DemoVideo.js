const VIDEO_URLS = [
  'https://cdn.razorpay.com/static/assets/magic-checkout/shiprocket-demo-step-1.mp4',
  'https://cdn.razorpay.com/static/assets/magic-checkout/shiprocket-demo-step-2.mp4',
  'https://cdn.razorpay.com/static/assets/magic-checkout/shiprocket-demo-step-3.mp4',
];

const DemoVideo = ({ step }) => {
  return (
    <div className="magic-checkout-demo-content text-center">
      <video
        className="magic-checkout-demo-video"
        key={VIDEO_URLS[step]}
        width="100%"
        controls
        autoPlay
        loop
      >
        <source src={VIDEO_URLS[step]} type="video/mp4" />
      </video>
      <div className="demo-video-subtext font-12 display-flex flex-center">
        <div className="shiprocket-demo-info-icon display-flex flex-center color-black color-white font-12">
          i
        </div>
        {step > 1
          ? 'Follow above steps on your Shiprocket dashboard'
          : 'Copy API credentials from your Shiprocket dashboard'}
      </div>
    </div>
  );
};

export default DemoVideo;
