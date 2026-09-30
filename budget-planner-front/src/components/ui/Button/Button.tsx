// Button/Button.tsx
import type { ButtonProps } from './types';
import styles from './Button.module.css';

function Button({ type, isLoading, loadingLabel, children }: ButtonProps) {
  function renderContent() {
    if (isLoading) {
      return loadingLabel;
    }
    return children;
  }

  return (
    <button className={styles.button} type={type} disabled={isLoading}>
      {renderContent()}
    </button>
  );
}

export default Button;
