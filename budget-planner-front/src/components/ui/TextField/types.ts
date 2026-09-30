// TextField/types.ts
export type TextFieldType = 'text' | 'email' | 'password';

export type TextFieldProps = {
  id: string;
  label: string;
  type: TextFieldType;
  value: string;
  placeholder: string;
  autoComplete: string;
  onChange: (value: string) => void;
};
