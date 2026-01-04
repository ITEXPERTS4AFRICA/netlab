<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Annotations newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Annotations newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Annotations query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAnnotations {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $cml_id
 * @property string|null $created
 * @property string|null $modified
 * @property array<array-key, mixed>|null $lab_description
 * @property int|null $node_count
 * @property string|null $state
 * @property string|null $lab_title
 * @property string|null $owner
 * @property int|null $link_count
 * @property array<array-key, mixed>|null $effective_permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $price_cents
 * @property string $currency
 * @property string|null $readme
 * @property string|null $short_description
 * @property array<array-key, mixed>|null $tags
 * @property array<array-key, mixed>|null $categories
 * @property string|null $difficulty_level
 * @property int|null $estimated_duration_minutes
 * @property bool $is_featured
 * @property bool $is_published
 * @property int $view_count
 * @property int $reservation_count
 * @property numeric|null $rating
 * @property int $rating_count
 * @property array<array-key, mixed>|null $requirements
 * @property array<array-key, mixed>|null $learning_objectives
 * @property array<array-key, mixed>|null $metadata
 * @property int $token_cost_per_hour
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LabDocumentationMedia> $activeDocumentationMedia
 * @property-read int|null $active_documentation_media_count
 * @property-read \App\Models\LabSnapshot|null $defaultSnapshot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LabDocumentationMedia> $documentationMedia
 * @property-read int|null $documentation_media_count
 * @property-read string $formatted_price
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read int|null $reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LabSnapshot> $snapshots
 * @property-read int|null $snapshots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UsageRecord> $usageRecords
 * @property-read int|null $usage_records_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereCmlId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereDifficultyLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereEffectivePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereEstimatedDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereLabDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereLabTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereLearningObjectives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereLinkCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereModified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereNodeCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab wherePriceCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereRatingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereReadme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereReservationCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereShortDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereTokenCostPerHour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lab whereViewCount($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLab {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $lab_id
 * @property string $type
 * @property string|null $title
 * @property string|null $description
 * @property string|null $file_path
 * @property string|null $file_url
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $thumbnail_path
 * @property int $order
 * @property bool $is_active
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $thumbnail_url
 * @property-read string|null $url
 * @property-read \App\Models\Lab $lab
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereFileUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereLabId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereThumbnailPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabDocumentationMedia whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLabDocumentationMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $lab_id
 * @property string $name
 * @property string|null $description
 * @property string $config_yaml
 * @property array<array-key, mixed>|null $config_json
 * @property array<array-key, mixed>|null $metadata
 * @property bool $is_default
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon $snapshot_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read string $formatted_size
 * @property-read int $size
 * @property-read \App\Models\Lab $lab
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot default()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot forLab($labId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereConfigJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereConfigYaml($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereLabId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereSnapshotAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LabSnapshot whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLabSnapshot {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $user_id
 * @property int|null $reservation_id
 * @property string $transaction_id
 * @property string|null $cinetpay_transaction_id
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property string|null $payment_method
 * @property string $customer_name
 * @property string|null $customer_surname
 * @property string $customer_email
 * @property string $customer_phone_number
 * @property string|null $description
 * @property array<array-key, mixed>|null $cinetpay_response
 * @property array<array-key, mixed>|null $webhook_data
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array<array-key, mixed>|null $metadata
 * @property-read string $formatted_amount
 * @property-read \App\Models\Reservation|null $reservation
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCinetpayResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCinetpayTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCustomerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCustomerPhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCustomerSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReservationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereWebhookData($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property int $cents_per_minute
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate whereCentsPerMinute($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperRate {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $user_id
 * @property int $lab_id
 * @property int|null $rate_id
 * @property \Illuminate\Support\Carbon $start_at
 * @property \Illuminate\Support\Carbon $end_at
 * @property string $status
 * @property int|null $estimated_cents
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $failed_attempts
 * @property int|null $tokens_cost
 * @property-read \App\Models\Lab $lab
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\Rate|null $rate
 * @property-read \App\Models\UsageRecord|null $usageRecord
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereEstimatedCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereFailedAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereLabId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereTokensCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReservation {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 * @property bool $is_encrypted
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $token_xof_rate
 * @property-read mixed $decrypted_value
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereTokenXofRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSetting {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $tokens
 * @property int $price_cents
 * @property string $currency
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $icon_svg
 * @property int $display_order
 * @property-read string $formatted_price
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereIconSvg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage wherePriceCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenPackage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTokenPackage {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $user_id
 * @property int $amount
 * @property string $type
 * @property string|null $description
 * @property string|null $reference_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenTransaction whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTokenTransaction {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $reservation_id
 * @property string $user_id
 * @property int $lab_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property int|null $duration_seconds
 * @property int|null $cost_cents
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Lab $lab
 * @property-read \App\Models\Reservation|null $reservation
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereCostCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereLabId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereReservationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUsageRecord {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $role
 * @property bool $is_active
 * @property string|null $avatar
 * @property string|null $bio
 * @property string|null $phone
 * @property string|null $organization
 * @property string|null $department
 * @property string|null $position
 * @property array<array-key, mixed>|null $skills
 * @property array<array-key, mixed>|null $certifications
 * @property array<array-key, mixed>|null $education
 * @property int $total_reservations
 * @property int $total_labs_completed
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $cml_username
 * @property string|null $cml_user_id UUID de l'utilisateur dans CML
 * @property bool $cml_admin Droits administrateur dans CML (peut différer du rôle local)
 * @property array<array-key, mixed>|null $cml_groups Groupes CML auxquels l'utilisateur appartient (array d'UUIDs)
 * @property string|null $cml_resource_pool_id Pool de ressources pour limiter les lancements de nœuds
 * @property string|null $cml_pubkey Clé publique SSH pour l'authentification du serveur de console
 * @property string|null $cml_directory_dn DN LDAP de l'utilisateur (si authentification LDAP)
 * @property bool|null $cml_opt_in Opt-in pour le formulaire de contact
 * @property string|null $cml_tour_version Version du tour d'introduction vue par l'utilisateur
 * @property string|null $cml_token Token JWT CML (stocké temporairement pour la session)
 * @property \Illuminate\Support\Carbon|null $cml_token_expires_at Date d'expiration du token CML
 * @property array<array-key, mixed>|null $cml_owned_labs Labs possédés par l'utilisateur dans CML (array d'UUIDs)
 * @property int $tokens_balance
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read int|null $reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TokenTransaction> $tokenTransactions
 * @property-read int|null $token_transactions_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCertifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlDirectoryDn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlGroups($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlOptIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlOwnedLabs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlPubkey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlResourcePoolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlTourVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCmlUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOrganization($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTokensBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTotalLabsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTotalReservations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $user_id
 * @property string $banned_by
 * @property string $reason
 * @property string|null $details
 * @property \Illuminate\Support\Carbon|null $banned_until Null = bannissement permanent
 * @property bool $is_permanent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $banner
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereBannedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereBannedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereIsPermanent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserBan whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserBan {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $user_id
 * @property string $issued_by
 * @property string $reason
 * @property string|null $details
 * @property string $severity
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $issuer
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereIssuedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperWarning {}
}

