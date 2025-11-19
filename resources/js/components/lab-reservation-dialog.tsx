import React, { useState, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Clock, Calendar, User, AlertTriangle, CheckCircle, Info, Zap, DollarSign } from 'lucide-react';
import { TimeSlotPicker, TimeSlot } from '@/components/ui/time-slot-picker';
import { router } from '@inertiajs/react';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';

interface Lab {
  id: string;
  title?: string;
  lab_title?: string;
  description?: string;
  short_description?: string;
  state: string;
  price_cents?: number;
  currency?: string;
  db_id?: number;
  estimated_duration_minutes?: number;
  difficulty_level?: string;
  rating?: number;
  rating_count?: number;
  tags?: string[];
  categories?: string[];
  metadata?: Record<string, unknown>;
}

interface LabReservationDialogProps {
  lab: Lab;
  children: React.ReactNode;
}

export default function LabReservationDialog({ lab, children }: LabReservationDialogProps) {
  const [open, setOpen] = useState(false);
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [selectedSlot, setSelectedSlot] = useState<TimeSlot | null>(null);
  const [isInstant, setIsInstant] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { data, setData, errors, reset } = useForm({
    lab_id: lab.id,
    start_at: '',
    end_at: '',
    instant: false,
  });

  // Generate time slots for the selected date
  const generateTimeSlots = (date: Date, labId: string): TimeSlot[] => {
    const slots: TimeSlot[] = [];
    const startHour = 6; // 6 AM
    const endHour = 22; // 10 PM
    const slotDuration = 4; // 4 hours

    // Utiliser une seed basée sur la date pour avoir des valeurs cohérentes
    const dateSeed = date.toDateString();
    let seed = 0;
    for (let i = 0; i < dateSeed.length; i++) {
      seed = ((seed << 5) - seed) + dateSeed.charCodeAt(i);
      seed = seed & seed; // Convert to 32bit integer
    }

    // Fonction pseudo-aléatoire basée sur la seed
    const seededRandom = () => {
      seed = (seed * 9301 + 49297) % 233280;
      return seed / 233280;
    };

    for (let hour = startHour; hour < endHour; hour += slotDuration) {
      const startTime = `${hour.toString().padStart(2, '0')}:00`;
      const endHourSlot = Math.min(hour + slotDuration, endHour);
      const endTime = `${endHourSlot.toString().padStart(2, '0')}:00`;

      // Utiliser le random seedé pour avoir des valeurs cohérentes par date
      const isAvailable = seededRandom() > 0.3; // 70% available
      const currentUsers = isAvailable ? Math.floor(seededRandom() * 2) : 0; // 0-1 users (lab is either free or occupied)
      const maxUsers = 1; // Each lab slot is occupied by one user only

      slots.push({
        id: `${labId}-${date.toDateString()}-${startTime}`,
        startTime,
        endTime,
        available: isAvailable,
        maxUsers,
        currentUsers,
      });
    }

    return slots;
  };

  // Mémoriser les time slots pour éviter les re-rendus infinis
  const timeSlots = useMemo(() => {
    return generateTimeSlots(selectedDate, lab.id);
  }, [selectedDate, lab.id]);

  const handleSlotSelect = (slot: TimeSlot) => {
    setSelectedSlot(slot);
    // Convert slot times to ISO format (UTC)
    const slotDate = selectedDate.toISOString().split('T')[0];
    let startDateTime = new Date(`${slotDate}T${slot.startTime}:00`);
    let endDateTime = new Date(`${slotDate}T${slot.endTime}:00`);

    // Si le créneau est dans le passé, utiliser demain
    const now = new Date();
    if (startDateTime <= now) {
      const tomorrow = new Date(now);
      tomorrow.setDate(tomorrow.getDate() + 1);
      const tomorrowDate = tomorrow.toISOString().split('T')[0];
      startDateTime = new Date(`${tomorrowDate}T${slot.startTime}:00`);
      endDateTime = new Date(`${tomorrowDate}T${slot.endTime}:00`);
    }

    setData({
      lab_id: lab.id,
      start_at: startDateTime.toISOString(),
      end_at: endDateTime.toISOString(),
      instant: isInstant,
    });
  };

  const pricePerHour = lab.price_cents ?? 0;

  // Calculer le prix estimé (aligné avec le backend) - mémorisé pour éviter les recalculs
  const estimatedPrice = useMemo(() => {
    if (!pricePerHour) return 0;

    let durationHours = 0;

    if (isInstant) {
      // Réservation instantanée : 4 heures
      durationHours = 4;
    } else if (selectedSlot) {
      // Calculer la durée en heures depuis le créneau sélectionné
    const start = new Date(`${selectedDate.toISOString().split('T')[0]}T${selectedSlot.startTime}:00`);
    const end = new Date(`${selectedDate.toISOString().split('T')[0]}T${selectedSlot.endTime}:00`);
      durationHours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
    } else if (data.start_at && data.end_at) {
      // Utiliser les dates du formulaire si disponibles
      const start = new Date(data.start_at);
      const end = new Date(data.end_at);
      durationHours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
    } else {
      // Pas de créneau sélectionné, retourner 0
      return 0;
    }

    // Aligné avec le backend : ceil(durationHours) * pricePerHour avec minimum 1 heure
    // Le backend fait : max(1, ceil($totalHours)) * $pricePerHour
    const hours = Math.max(1, Math.ceil(durationHours));
    return hours * pricePerHour;
  }, [pricePerHour, isInstant, selectedSlot, selectedDate, data.start_at, data.end_at]);

  const displayedTotalCents = estimatedPrice > 0 ? estimatedPrice : pricePerHour;

  const reservationSummary = useMemo(() => {
    if (isInstant) {
      const start = new Date();
      const end = new Date(start.getTime() + 4 * 60 * 60 * 1000);
      return {
        mode: 'Instantanée',
        date: start.toLocaleDateString('fr-FR', { weekday: 'short', day: '2-digit', month: 'short' }),
        time: `${start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })} - ${end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`,
        duration: '4 h',
      };
    }
    if (selectedSlot) {
      const slotDate = selectedDate.toLocaleDateString('fr-FR', { weekday: 'short', day: '2-digit', month: 'short' });
      const start = new Date(`${selectedDate.toISOString().split('T')[0]}T${selectedSlot.startTime}:00`);
      const end = new Date(`${selectedDate.toISOString().split('T')[0]}T${selectedSlot.endTime}:00`);
      const diffHours = Math.max(1, Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60)));
      return {
        mode: 'Planifiée',
        date: slotDate,
        time: `${selectedSlot.startTime} - ${selectedSlot.endTime}`,
        duration: `${diffHours} h`,
      };
    }
    return null;
  }, [isInstant, selectedSlot, selectedDate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // Vérifier qu'un créneau est sélectionné
    if (!selectedSlot && !isInstant) {
      alert('Veuillez sélectionner un créneau horaire');
      return;
    }

    // Préparer les dates
    let startAt: string;
    let endAt: string;

    if (isInstant) {
      // Réservation instantanée : maintenant + 4 heures
      const now = new Date();
      // Ajouter 1 seconde pour éviter les problèmes de timing
      const startTime = new Date(now.getTime() + 1000);
      const end = new Date(startTime.getTime() + 4 * 60 * 60 * 1000); // +4 heures
      startAt = startTime.toISOString();
      endAt = end.toISOString();

      console.log('Instant reservation times:', {
        startAt,
        endAt,
        now: now.toISOString(),
        duration_hours: 4,
      });
    } else if (data.start_at && data.end_at) {
      // Utiliser les dates du formulaire
    const startDateTime = new Date(data.start_at);
    const endDateTime = new Date(data.end_at);

      // S'assurer que les dates sont dans le futur
      const now = new Date();
      if (startDateTime <= now) {
        // Si le créneau est passé, utiliser demain
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        const [startHours, startMinutes] = selectedSlot?.startTime.split(':') || ['09', '00'];
        const [endHours, endMinutes] = selectedSlot?.endTime.split(':') || ['13', '00'];

        startDateTime.setFullYear(tomorrow.getFullYear(), tomorrow.getMonth(), tomorrow.getDate());
        startDateTime.setHours(parseInt(startHours), parseInt(startMinutes), 0, 0);

        endDateTime.setFullYear(tomorrow.getFullYear(), tomorrow.getMonth(), tomorrow.getDate());
        endDateTime.setHours(parseInt(endHours), parseInt(endMinutes), 0, 0);
      }

      startAt = startDateTime.toISOString();
      endAt = endDateTime.toISOString();
    } else if (selectedSlot) {
      // Générer les dates depuis le créneau sélectionné
      const slotDate = selectedDate.toISOString().split('T')[0];
      const startDateTime = new Date(`${slotDate}T${selectedSlot.startTime}:00`);
      const endDateTime = new Date(`${slotDate}T${selectedSlot.endTime}:00`);

      // S'assurer que les dates sont dans le futur
      const now = new Date();
      if (startDateTime <= now) {
        // Si le créneau est passé, utiliser demain
        startDateTime.setDate(startDateTime.getDate() + 1);
        endDateTime.setDate(endDateTime.getDate() + 1);
      }

      startAt = startDateTime.toISOString();
      endAt = endDateTime.toISOString();
    } else {
      alert('Veuillez sélectionner un créneau horaire');
      return;
    }

    const startDateTime = new Date(startAt);
    const endDateTime = new Date(endAt);

    if (endDateTime <= startDateTime) {
      alert('L\'heure de fin doit être après l\'heure de début');
      return;
    }

    setIsSubmitting(true);
    // Utiliser l'API REST au lieu de Inertia pour gérer le paiement
    try {
      const requestData = {
        lab_id: lab.id, // cml_id (UUID)
        start_at: startAt,
        end_at: endAt,
        instant: isInstant || false,
      };

      console.log('Sending reservation request:', {
        ...requestData,
        is_instant: isInstant,
        lab_id_type: typeof lab.id,
        lab_id_length: lab.id?.length,
      });

      // Utiliser cml_id pour trouver le lab
      const response = await fetch(`/api/labs/reserve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin', // Inclure les cookies de session pour l'authentification
        body: JSON.stringify(requestData),
      });

      let result;
      let responseText = '';
      try {
        responseText = await response.text();
        console.log('Raw response:', responseText);

        // Nettoyer la réponse si elle contient du CSS du SDK CinetPay
        // Le SDK peut injecter du CSS avant le JSON
        let cleanedResponse = responseText;
        if (responseText.includes('</style>')) {
          // Extraire le JSON après le </style>
          const jsonStart = responseText.indexOf('</style>') + 8;
          cleanedResponse = responseText.substring(jsonStart).trim();
          console.log('Cleaned response (removed CSS):', cleanedResponse);
        }

        result = JSON.parse(cleanedResponse);
      } catch (e) {
        console.error('Failed to parse response:', e);
        console.error('Response status:', response.status);
        console.error('Response headers:', Object.fromEntries(response.headers.entries()));
        console.error('Response text:', responseText);
        throw new Error(`Réponse invalide du serveur (${response.status}): ${responseText.substring(0, 200)}`);
      }

      if (!response.ok) {
        // Gérer les erreurs de timeout CinetPay
        if (result.error && (result.code === 'CONNECTION_TIMEOUT' || result.is_timeout)) {
          const timeoutMessage = result.error || 'L\'API de paiement CinetPay ne répond pas. Veuillez réessayer plus tard.';
          console.error('CinetPay timeout error:', result);
          
          // Si la réservation a été créée, rediriger vers les réservations avec un message
          if (result.reservation && result.can_retry_payment) {
            console.log('Redirection vers /labs/my-reserved avec réservation:', result.reservation.id);
            toast.warning(
              result.message || 'La réservation a été créée mais le paiement n\'a pas pu être initialisé. Vous pourrez réessayer le paiement depuis la page de vos réservations.',
              {
                duration: 8000,
              }
            );
            setOpen(false);
            
            // Utiliser un petit délai pour permettre au toast de s'afficher
            setTimeout(() => {
              router.visit('/labs/my-reserved', {
                method: 'get',
                preserveScroll: true,
                data: {
                  reservation_id: result.reservation.id,
                  payment_error: true,
                  error_message: timeoutMessage,
                },
              });
            }, 500);
            return;
          }
          
          // Si pas de réservation, afficher juste l'erreur
          toast.error(timeoutMessage, {
            duration: 10000,
          });
          return;
        }

        // Gérer les erreurs de validation Laravel (422)
        if (response.status === 422 && result.errors) {
          // Erreurs de validation Laravel
          const validationErrors = Object.entries(result.errors)
            .map(([field, messages]) => {
              const msgArray = Array.isArray(messages) ? messages : [messages];
              return `${field}: ${msgArray.join(', ')}`;
            })
            .join('; ');
          console.error('Validation errors:', result.errors);
          throw new Error(validationErrors || 'Erreurs de validation');
        }

        // Gérer les erreurs détaillées de CinetPay
        let errorMessage = typeof result.error === 'string'
          ? result.error
          : result.message || 'Erreur lors de la création de la réservation';

        // Si c'est une erreur CinetPay avec code et description
        if (result.code && result.description) {
          errorMessage = `${errorMessage} (Code: ${result.code})`;
          if (result.description) {
            errorMessage += ` - ${result.description}`;
          }
        }

        console.error('Reservation error:', {
          status: response.status,
          error: result.error,
          code: result.code,
          description: result.description,
          errors: result.errors,
          fullResult: result,
        });

        // Si la réservation a été créée mais le paiement a échoué, rediriger vers les réservations
        if (result.reservation && result.can_retry_payment) {
          toast.warning('La réservation a été créée mais le paiement n\'a pas pu être initialisé. Vous pourrez réessayer le paiement plus tard.', {
            duration: 8000,
          });
          setOpen(false);
          router.visit('/labs/my-reserved', {
            method: 'get',
            preserveScroll: true,
            data: {
              reservation_id: result.reservation.id,
              payment_error: true,
              error_message: errorMessage,
            },
          });
          return;
        }

        throw new Error(errorMessage);
      }

      // Log pour déboguer le paiement
      console.log('Reservation response:', {
        requires_payment: result.requires_payment,
        payment_url: result.payment_url,
        estimated_cents: result.reservation?.estimated_cents,
        payment: result.payment,
        fullResult: result,
      });

      // Si un paiement est requis, rediriger vers CinetPay IMMÉDIATEMENT
      if (result.requires_payment === true && result.payment_url) {
        console.log('✅ Redirection vers CinetPay:', result.payment_url);
        // Fermer le dialog avant la redirection
        setOpen(false);
        // Redirection immédiate vers CinetPay
        globalThis.location.href = result.payment_url;
        return;
      }

      // Si requires_payment est true mais pas de payment_url, afficher un message
      if (result.requires_payment === true && !result.payment_url) {
        console.error('❌ Paiement requis mais pas de payment_url fourni', result);
        setUploadError('Erreur: URL de paiement non disponible. Veuillez contacter le support.');
        return;
      }

      // Si payment_url existe même sans requires_payment explicite, rediriger quand même
      if (result.payment_url && !result.requires_payment) {
        console.log('⚠️ payment_url trouvé sans requires_payment, redirection quand même:', result.payment_url);
        setOpen(false);
        globalThis.location.href = result.payment_url;
        return;
      }

      // Sinon, rediriger vers les réservations (lab gratuit ou paiement non requis)
      console.log('✅ Pas de paiement requis, redirection vers /labs/my-reserved');
        setOpen(false);
        reset();
        router.visit('/labs/my-reserved', {
          method: 'get',
          preserveScroll: true,
        });
    } catch (error) {
      console.error('Reservation error:', error);
      // Afficher l'erreur dans le formulaire
      const errorMessage = error instanceof Error ? error.message : 'Une erreur est survenue';
      // Ne pas utiliser setData pour les erreurs, utiliser un state local
      setUploadError(errorMessage);

      // Afficher aussi dans une alerte visuelle
      alert(errorMessage);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (!isOpen) {
      reset();
      setUploadError(null); // Réinitialiser l'erreur lors de la fermeture
    }
  };



  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        {children}
      </DialogTrigger>
      <DialogContent className="sm:max-w-[650px] max-h-[90vh] overflow-y-auto">
        <DialogHeader className="space-y-4">
          <div className="flex items-center justify-between">
            <DialogTitle className="flex items-center gap-3 text-xl">
              <div className="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <Calendar className="h-5 w-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <h2 className="font-semibold">Book Lab Reservation</h2>
                <p className="text-sm font-normal text-muted-foreground">Schedule your lab access session</p>
              </div>
            </DialogTitle>
          </div>
          <DialogDescription>
            Schedule a reservation for {lab.title || `Lab ${lab.id}`}. Select your preferred date and time slot for lab access.
          </DialogDescription>
        </DialogHeader>

        <Card className="mt-4">
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <h3 className="font-medium flex items-center gap-2">
                <User className="h-4 w-4 text-muted-foreground" />
                {lab.title || lab.lab_title || `Lab ${lab.id}`}
              </h3>
              <Badge
                variant={lab.state === 'STOPPED' ? 'destructive' : lab.state === 'RUNNING' ? 'default' : 'secondary'}
                className="flex items-center gap-1"
              >
                {lab.state === 'STOPPED' ? (
                  <AlertTriangle className="h-3 w-3" />
                ) : lab.state === 'RUNNING' ? (
                  <CheckCircle className="h-3 w-3" />
                ) : (
                  <Clock className="h-3 w-3" />
                )}
                {lab.state}
              </Badge>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Description */}
            {(lab.description || lab.short_description) && (
              <p className="text-sm text-muted-foreground leading-relaxed">
                {lab.short_description || lab.description}
              </p>
            )}

            {/* Métadonnées du lab */}
            <div className="grid grid-cols-2 gap-3 pt-2">
              {/* Prix */}
              {lab.price_cents && lab.price_cents > 0 && (
                <div className="flex items-center gap-2 p-2 rounded-md bg-muted/50">
                  <DollarSign className="h-4 w-4 text-primary" />
                  <div className="text-xs">
                    <p className="font-medium">Prix/heure</p>
                    <p className="text-muted-foreground">
                      {(lab.price_cents / 100).toLocaleString('fr-FR')} {lab.currency || 'XOF'}
                    </p>
                  </div>
                </div>
              )}

              {/* Durée estimée */}
              {lab.estimated_duration_minutes && (
                <div className="flex items-center gap-2 p-2 rounded-md bg-muted/50">
                  <Clock className="h-4 w-4 text-primary" />
                  <div className="text-xs">
                    <p className="font-medium">Durée</p>
                    <p className="text-muted-foreground">
                      {lab.estimated_duration_minutes} min
                    </p>
                  </div>
                </div>
              )}

              {/* Difficulté */}
              {lab.difficulty_level && (
                <div className="flex items-center gap-2 p-2 rounded-md bg-muted/50">
                  <AlertTriangle className="h-4 w-4 text-primary" />
                  <div className="text-xs">
                    <p className="font-medium">Difficulté</p>
                    <p className="text-muted-foreground capitalize">
                      {lab.difficulty_level}
                    </p>
                  </div>
                </div>
              )}

              {/* Note */}
              {lab.rating && lab.rating > 0 && (
                <div className="flex items-center gap-2 p-2 rounded-md bg-muted/50">
                  <CheckCircle className="h-4 w-4 text-primary" />
                  <div className="text-xs">
                    <p className="font-medium">Note</p>
                    <p className="text-muted-foreground">
                      {lab.rating.toFixed(1)} ⭐ ({lab.rating_count || 0})
                    </p>
                  </div>
                </div>
              )}
            </div>

            {/* Tags */}
            {lab.tags && Array.isArray(lab.tags) && lab.tags.length > 0 && (
              <div className="flex flex-wrap gap-2 pt-2">
                {lab.tags.map((tag: string, index: number) => (
                  <Badge key={index} variant="outline" className="text-xs">
                    {tag}
                  </Badge>
                ))}
              </div>
            )}

            {/* État du lab */}
            {lab.state !== 'RUNNING' && (
              <div className="flex items-start gap-2 p-3 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <Info className="h-4 w-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
                <div className="text-sm">
                  <p className="text-amber-800 dark:text-amber-200 font-medium mb-1">Important Note</p>
                  <p className="text-amber-700 dark:text-amber-300">
                    This lab is currently {lab.state.toLowerCase()}. Ensure the lab is running before your reservation starts.
                  </p>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <Separator />

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Date Selector */}
          <div className="flex items-center gap-2">
            <Button
              type="button"
              variant={selectedDate.toDateString() === new Date().toDateString() ? 'default' : 'outline'}
              size="sm"
              onClick={() => setSelectedDate(new Date())}
            >
              Today
            </Button>
            <Button
              type="button"
              variant={selectedDate.toDateString() === new Date(Date.now() + 24 * 60 * 60 * 1000).toDateString() ? 'default' : 'outline'}
              size="sm"
              onClick={() => setSelectedDate(new Date(Date.now() + 24 * 60 * 60 * 1000))}
            >
              Tomorrow
            </Button>
          </div>

          <Separator />

          {/* Option Réservation Instantanée */}
          <div className="flex items-center space-x-2 p-4 rounded-lg bg-gradient-to-r from-primary/5 to-accent/5 border border-primary/20">
            <Checkbox
              id="instant-reservation"
              checked={isInstant}
              onCheckedChange={(checked) => {
                setIsInstant(checked === true);
                if (checked) {
                  // Sélectionner automatiquement le créneau actuel
                  setSelectedDate(new Date());
                  const now = new Date();
                  const currentHour = now.getHours();
                  const nextHour = Math.min(currentHour + 4, 22);
                  const startTime = `${currentHour.toString().padStart(2, '0')}:00`;
                  const endTime = `${nextHour.toString().padStart(2, '0')}:00`;

                  const instantSlot: TimeSlot = {
                    id: `${lab.id}-instant-${Date.now()}`,
                    startTime,
                    endTime,
                    available: true,
                    maxUsers: 1,
                    currentUsers: 0,
                  };

                  setSelectedSlot(instantSlot);
                  handleSlotSelect(instantSlot);
                }
              }}
            />
            <Label htmlFor="instant-reservation" className="flex items-center gap-2 cursor-pointer">
              <Zap className="h-4 w-4 text-yellow-500" />
              <span className="font-medium">Réservation instantanée</span>
              <span className="text-xs text-muted-foreground">(Démarrage immédiat)</span>
            </Label>
          </div>

          {/* Affichage du prix */}
          {pricePerHour > 0 && (
            <div className="flex items-center justify-between p-4 rounded-lg bg-muted/50 border">
              <div className="flex items-center gap-2">
                <DollarSign className="h-5 w-5 text-primary" />
                <span className="font-medium">
                  {estimatedPrice > 0 ? 'Prix estimé' : 'Prix par heure'}
                </span>
              </div>
              <span className="text-lg font-bold text-primary">
                {estimatedPrice > 0
                  ? `${(estimatedPrice / 100).toLocaleString('fr-FR')} ${lab.currency || 'XOF'}`
                  : `${(pricePerHour / 100).toLocaleString('fr-FR')} ${lab.currency || 'XOF'}/h`}
              </span>
            </div>
          )}

          {/* Time Slot Picker */}
          {!isInstant && (
          <TimeSlotPicker
            selectedDate={selectedDate}
            slots={timeSlots}
            onSlotSelect={handleSlotSelect}
            selectedSlot={selectedSlot}
            maxSlotsPerUser={3}
            userCurrentSlots={1}
          />
          )}

          {isInstant && selectedSlot && (
            <div className="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
              <div className="flex items-center gap-2 mb-2">
                <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                <span className="font-medium text-green-800 dark:text-green-200">
                  Réservation instantanée sélectionnée
                </span>
              </div>
              <p className="text-sm text-green-700 dark:text-green-300">
                Le lab démarrera immédiatement après la confirmation.
                {estimatedPrice > 0 && ' Le paiement sera requis avant l\'accès.'}
              </p>
            </div>
          )}

          {(isInstant || selectedSlot) && (
            <Card className="border border-dashed border-primary/30 bg-primary/5">
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <h4 className="text-base font-semibold">Panier & validation</h4>
                  {reservationSummary && (
                    <span
                      className={`px-2 py-1 rounded-full text-xs font-semibold ${
                        reservationSummary.mode === 'Instantanée'
                          ? 'bg-green-500/15 text-green-700 dark:text-green-200'
                          : 'bg-blue-500/15 text-blue-700 dark:text-blue-200'
                      }`}
                    >
                      {reservationSummary.mode}
                    </span>
                  )}
                </div>
                <p className="text-sm text-muted-foreground">
                  Vérifiez les détails avant de lancer le paiement.
                </p>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div>
                  <p className="text-xs text-muted-foreground">Lab sélectionné</p>
                  <p className="font-medium">{lab.title || lab.lab_title || `Lab ${lab.id}`}</p>
                  {(lab.short_description || lab.description) && (
                    <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                      {lab.short_description || lab.description}
                    </p>
                  )}
                </div>
                {reservationSummary && (
                  <div className="grid grid-cols-2 gap-3 text-xs">
                    <div>
                      <p className="text-muted-foreground">Mode</p>
                      <p className="font-medium">{reservationSummary.mode}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Date</p>
                      <p className="font-medium">{reservationSummary.date}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Créneau</p>
                      <p className="font-medium">{reservationSummary.time}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Durée</p>
                      <p className="font-medium">{reservationSummary.duration}</p>
                    </div>
                  </div>
                )}
                <Separator />
                <div className="flex items-center justify-between text-base font-semibold">
                  <span>Total</span>
                  <span>
                    {displayedTotalCents > 0
                      ? `${(displayedTotalCents / 100).toLocaleString('fr-FR')} ${lab.currency || 'XOF'}`
                      : 'Gratuit'}
                  </span>
                </div>
              </CardContent>
            </Card>
          )}

          {(uploadError || Object.keys(errors).length > 0) && (
            <div className="p-3 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
              <div className="text-sm text-red-700 dark:text-red-300">
                {uploadError && (
                  <p className="mb-1">
                    <strong>Erreur:</strong> {uploadError}
                  </p>
                )}
                {Object.entries(errors).map(([field, message]) => {
                  const errorText = Array.isArray(message)
                    ? message.join(', ')
                    : typeof message === 'string'
                    ? message
                    : JSON.stringify(message);
                  return (
                    <p key={field} className="mb-1">
                      <strong>{field === 'general' ? 'Erreur' : field}:</strong> {errorText}
                    </p>
                  );
                })}
              </div>
            </div>
          )}

          <div className="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-md border border-blue-200 dark:border-blue-800">
            <div className="flex items-start gap-2">
              <Info className="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
              <div className="text-sm space-y-1">
                <p className="text-blue-800 dark:text-blue-200 font-medium">Reservation Guidelines</p>
                <ul className="text-blue-700 dark:text-blue-300 space-y-0.5 text-xs">
                  <li>• 4-hour time slots available</li>
                  <li>• Maximum 3 reservations per day per user</li>
                  <li>• Lab access requires the lab to be in RUNNING state</li>
                </ul>
              </div>
            </div>
          </div>

          <Separator />

          <div className="flex justify-end gap-3 pt-2">
            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={isSubmitting || (!isInstant && !selectedSlot)}
              className="min-w-32"
            >
              {isSubmitting ? (
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                  {displayedTotalCents > 0 ? 'Préparation du paiement...' : 'Création...'}
                </div>
              ) : (
                <div className="flex items-center gap-2">
                  <CheckCircle className="h-4 w-4" />
                  {displayedTotalCents > 0 ? 'Valider et payer' : 'Confirmer'}
                </div>
              )}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
