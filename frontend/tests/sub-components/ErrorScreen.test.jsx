import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';

import ErrorScreenComponent from '../../src/sub-components/ErrorScreenComponent.jsx';

describe('ErrorScreenComponent', () => {
  it('renders error message', () => {
    render(<ErrorScreenComponent errorMessage="Something went wrong" />);
    expect(screen.getByText('Something went wrong')).toBeInTheDocument();
  });

it('calls reload on button click', () => {
  const originalLocation = window.location;
  delete window.location; // remove readonly property
  window.location = { reload: vi.fn() }; // mock reload

  render(<ErrorScreenComponent errorMessage="Error" />);
  const button = screen.getByRole('button', { name: /reload/i });
  fireEvent.click(button);

  expect(window.location.reload).toHaveBeenCalled();

  window.location = originalLocation; // restore original
});

});
