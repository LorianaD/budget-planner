// hooks/useAsyncSubmit.ts
import { useState } from 'react';

export type UseAsyncSubmitResult = {
  isSubmitting: boolean;
  errorMessage: string | null;
  submit: (action: () => Promise<void>) => Promise<void>;
};

function useAsyncSubmit(): UseAsyncSubmitResult {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  async function submit(action: () => Promise<void>) {
    setIsSubmitting(true);
    setErrorMessage(null);

    try {
      await action();
    } catch (error) {
      if (error instanceof Error) {
        setErrorMessage(error.message);
      } else {
        setErrorMessage('Une erreur inattendue est survenue.');
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  return { isSubmitting, errorMessage, submit };
}

export default useAsyncSubmit;
