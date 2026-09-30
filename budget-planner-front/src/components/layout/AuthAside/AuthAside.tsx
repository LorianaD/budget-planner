// AuthAside/AuthAside.tsx
import type { AuthAsideProps } from './types';
import styles from './AuthAside.module.css';

function AuthAside({ headline, decoration, footerText }: AuthAsideProps) {
  return (
    <aside className={styles['auth-aside']}>
      <p className={styles['auth-aside__brand']}>budget-planner</p>

      <div className={styles['auth-aside__content']}>
        <p className={styles['auth-aside__headline']}>{headline}</p>
        {decoration}
      </div>

      <p className={styles['auth-aside__footer']}>{footerText}</p>
    </aside>
  );
}

export default AuthAside;
