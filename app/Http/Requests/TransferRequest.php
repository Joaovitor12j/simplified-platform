<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Application\DTOs\TransferDTO;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'numeric', 'min:0.01'],
            'payer' => ['required', 'uuid', 'exists:users,id'],
            'payee' => ['required', 'uuid', 'exists:users,id', 'different:payer'],
        ];
    }

    public function toDTO(): TransferDTO
    {
        $validated = $this->validated();

        return new TransferDTO(
            payerId: (string) $validated['payer'],
            payeeId: (string) $validated['payee'],
            amount: number_format((float) $validated['value'], 2, '.', '')
        );
    }
}
