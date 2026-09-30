// TextField/TextField.tsx
import type { ChangeEvent } from 'react';
import type { TextFieldProps } from './types';
import styles from './TextField.module.css';

function TextField({ id, label, type, value, placeholder, autoComplete, onChange }: TextFieldProps) {
  function handleChange(event: ChangeEvent<HTMLInputElement>) {
    onChange(event.target.value);
  }

  return (
    <div className={styles['text-field']}>
      <label className={styles['text-field__label']} htmlFor={id}>{label}</label>
      <input
        className={styles['text-field__input']}
        id={id}
        name={id}
        type={type}
        value={value}
        placeholder={placeholder}
        autoComplete={autoComplete}
        onChange={handleChange}
        required
      />
    </div>
  );
}

export default TextField;
