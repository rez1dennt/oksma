import { scrollbarCompensation } from './overlay-motion.js';

export function createScrollLock(viewport, documentElement, body) {
  let count = 0;
  let savedX = 0;
  let savedY = 0;

  const release = () => {
    body.classList.remove('is-locked');
    documentElement.classList.remove('is-scroll-locked');
    documentElement.style.removeProperty('--scrollbar-compensation');
    if (viewport.scrollX !== savedX || viewport.scrollY !== savedY) {
      viewport.scrollTo(savedX, savedY);
    }
  };

  return {
    lock() {
      if (count === 0) {
        savedX = viewport.scrollX;
        savedY = viewport.scrollY;
        const compensation = scrollbarCompensation(viewport.innerWidth, documentElement.clientWidth);
        documentElement.style.setProperty('--scrollbar-compensation', `${compensation}px`);
        documentElement.classList.add('is-scroll-locked');
        body.classList.add('is-locked');
      }
      count += 1;
    },
    unlock() {
      count = Math.max(0, count - 1);
      if (count !== 0 || !body.classList.contains('is-locked')) return;
      release();
    },
    isLocked() {
      return count > 0;
    },
  };
}
