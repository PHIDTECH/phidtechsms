import io
from pathlib import Path
path = Path('app/Http/Controllers/ContactController.php')
text = path.read_text()
lines = text.splitlines()
start = None
end = None
for i, line in enumerate(lines):
    if "foreach ($processedData as $index => $record) {" in line:
        start = i
        break
if start is None:
    raise SystemExit('start not found')
# find end of block: loop block ends before line with 'DB::commit();' after foreach
for j in range(start, len(lines)):
    if 'DB::commit();' in lines[j]:
        end = j - 1
        break
if end is None:
    raise SystemExit('end not found')
new_block = '''            foreach ($processedData as $index => $record) {
                try {
                    $name = isset($record['name']) ? trim((string) $record['name']) : '';
                    $rawPhone = $record['phone_number'] ?? null;
                    $email = isset($record['email']) ? trim((string) $record['email']) : null;

                    $formattedPhone = $this->validateAndFormatPhoneNumber($rawPhone);

                    if (!$formattedPhone) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid phone number.";
                        $skipped++;
                        continue;
                    }

                    if ($name === '') {
                        $name = $this->generateFallbackName($formattedPhone, $index);
                    }

                    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $email = null;
                    }

                    $existingContact = Contact::where('user_id', $user->id)
                        ->where('phone_number', $formattedPhone)
                        ->first();

                    if ($existingContact) {
                        if ($groupId) {
                            $existingContact->groups()->syncWithoutDetaching([$groupId]);
                        }

                        if ($skipDuplicates) {
                            $skipped++;
                            continue;
                        }
                    }

                    $contact = Contact::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'phone_number' => $formattedPhone,
                        ],
                        [
                            'name' => $name,
                            'email' => $email,
                            'is_active' => $existingContact ? $existingContact->is_active : true,
                        ]
                    );

                    if ($groupId) {
                        $contact->groups()->syncWithoutDetaching([$groupId]);
                        \\Log::info('Contact attached to group', ['contact_id' => $contact->id, 'group_id' => $groupId]);
                    }

                    $imported++;
                } catch (\\Exception $e) {
                    $errorMsg = "Row " . ($index + 1) . ": " . $e->getMessage();
                    $errors[] = $errorMsg;
                    \\Log::error('Error processing contact', ['index' => $index, 'error' => $e->getMessage()]);
                }
            }
'''
new_lines = lines[:start] + new_block.splitlines() + lines[end+1:]
path.write_text("\n".join(new_lines) + "\n")
