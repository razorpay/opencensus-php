export const scrollToTop = (ref) => {
  ref?.current?.scroll({
    top: 0,
    behavior: 'smooth',
  });
};