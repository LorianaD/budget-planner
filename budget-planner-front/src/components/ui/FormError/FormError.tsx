// FormError/FormError.tsx
import type { FormErrorProps } from './types';
import styles from './FormError.module.css';

function FormError({ message }: FormErrorProps) {
  if (message === null) {
    return null;
  }

  return (
    <p className={styles['form-error']} role="alert">{message}</p>
  );
}

export default FormError;
