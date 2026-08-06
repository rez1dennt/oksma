import { scrollbarCompensation } from './overlay-motion.js';

export function createScrollLock(viewport, documentElement, body) {
  let count = 0;
  let savedX = 0;
  let savedY = 0;

  return {
    lock() {
      if (count === 0) {
        savedX = viewport.scrollX;
        savedY = viewport.scrollY;
        const compensation = scrollbarCompensation(viewport.innerWidth, documentElement.clientWidth);
        documentElement.style.setProperty('--scrollbar-compensation', `${compensation}px`);
        documentElement.style.setProperty('--scroll-lock-x', `${-savedX}px`);
        documentElement.style.setProperty('--scroll-lock-y', `${-savedY}px`);
        body.classList.add('is-locked');
      }
      count += 1;
    },
    unlock() {
      count = Math.max(0, count - 1);
      if (count !== 0 || !body.classList.contains('is-locked')) return;
      body.classList.remove('is-locked');
      documentElement.style.removeProperty('--scrollbar-compensation');
      documentElement.style.removeProperty('--scroll-lock-x');
      documentElement.style.removeProperty('--scroll-lock-y');
      viewport.scrollTo(savedX, savedY);
    },
    isLocked() {
      return count > 0;
    },
  };
}
