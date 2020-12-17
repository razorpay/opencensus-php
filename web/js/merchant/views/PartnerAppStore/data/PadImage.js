// Helper function to add padding to images

function PadImage(brandColor) {
  return (props) => (
    <div className="content-image-holder" style={{ backgroundColor: brandColor }}>
      <img className="content-image" {...props} />
    </div>
  );
}

export default PadImage;
