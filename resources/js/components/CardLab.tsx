import React from 'react'
import { motion } from 'framer-motion'
import LabReservationDialog from '@/components/lab-reservation-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Clock, Calendar, CheckCircle } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTrigger } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {  } from 'recharts/types/util/ChartUtils';
import AnnotationLab from '@/components/AnnotationLab';

interface Props {
    lab: any
    index: number
}

/**
 * CardLab component
 *
 * @param {Object} lab - lab object from api
 * @param {number} index - index of the lab in the list
 *
 * @returns {React.Component} - CardLab component
 *
 * This component renders a card for a lab with a status indicator, stats grid and actions
 */
export default function CardLab({ lab, index }: Props) {
  return (
      <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-card via-card/95 to-card/80 transition-all duration-500 hover:shadow-2xl hover:shadow-primary/5">
          {/* Status gradient stripe */}
          <motion.div
              className={`absolute top-0 right-0 left-0 h-1 ${
                  lab.state === 'DEFINED_ON_CORE'
                      ? 'bg-gradient-to-r from-[hsl(var(--chart-3))] to-[hsl(var(--chart-3))/70]'
                      : lab.state === 'STOPPED'
                        ? 'bg-gradient-to-r from-destructive to-destructive/70'
                        : 'bg-gradient-to-r from-[hsl(var(--chart-2))] to-[hsl(var(--chart-2))/70]'
              }`}
              initial={false}
              animate={{
                  scaleX: [0, 1],
                  transformOrigin: 'left',
              }}
              transition={{ duration: 0.6, delay: index * 0.05 }}
          />

          <CardHeader className="pb-4">
              <div className="flex items-start justify-between">
                  <div className="min-w-0 flex-1">
                      <CardTitle className="mb-3 line-clamp-2 text-xl font-bold transition-colors duration-300 group-hover:text-primary">
                          {lab.lab_title}
                      </CardTitle>
                      {getStatusBadge(lab.state)}
                  </div>

                  {/* Status indicator */}
                  <motion.div
                      className={`rounded-xl p-3 ${
                          lab.state === 'DEFINED_ON_CORE'
                              ? 'border border-[hsl(var(--chart-3)/20)] bg-[hsl(var(--chart-3)/10)]'
                              : lab.state === 'STOPPED'
                                ? 'border border-destructive/20 bg-destructive/10'
                                : 'border border-[hsl(var(--chart-2)/20)] bg-[hsl(var(--chart-2)/10)]'
                      }`}
                      whileHover={{ scale: 1.05 }}
                      transition={{ duration: 0.2 }}
                  >
                      {lab.state === 'DEFINED_ON_CORE' ? (
                          <CheckCircle className="h-6 w-6 text-[hsl(var(--chart-3))]" />
                      ) : lab.state === 'STOPPED' ? (
                          <AlertCircle className="h-6 w-6 text-destructive" />
                      ) : (
                          <Clock className="h-6 w-6 text-[hsl(var(--chart-2))]" />
                      )}
                  </motion.div>
              </div>
          </CardHeader>

          <CardContent className="space-y-6">
              {/* Description */}
              {lab.lab_description && <p className="line-clamp-2 text-sm leading-relaxed text-muted-foreground">{lab.lab_description}</p>}

              {/* Stats Grid */}
              <div className="grid grid-cols-2 gap-4">
                  <motion.div className="flex items-center gap-3 rounded-xl border border-border/50 bg-muted/30 p-4" whileHover={{ scale: 1.02 }}>
                      <Network className="h-5 w-5 text-[hsl(var(--chart-4))]" />
                      <div>
                          <div className="text-lg font-semibold">{lab.node_count}</div>
                          <div className="text-xs text-muted-foreground">appareils</div>
                      </div>
                  </motion.div>

                  <motion.div className="flex items-center gap-3 rounded-xl border border-border/50 bg-muted/30 p-4" whileHover={{ scale: 1.02 }}>
                      <Clock className="h-5 w-5 text-[hsl(var(--chart-5))]" />
                      <div>
                          <div className="text-sm font-medium">
                              {new Date(lab.modified).toLocaleDateString('en-US', {
                                  month: 'short',
                                  day: 'numeric',
                              })}
                          </div>
                          <div className="text-xs text-muted-foreground">modifié</div>
                      </div>
                  </motion.div>
              </div>

              {/* Actions */}
              <div className="flex flex-wrap items-center gap-2 border-t border-border/50 pt-2">
                  <LabReservationDialog
                      lab={{
                          id: lab.id,
                          title: lab.lab_title,
                          description: lab.lab_description,
                          state: lab.state,
                      }}
                  >
                      <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
                          <Button
                              size="sm"
                              className={`h-10 px-6 shadow-lg ${
                                  lab.state === 'DEFINED_ON_CORE'
                                      ? 'bg-gradient-to-r from-[hsl(var(--chart-3))] to-[hsl(var(--chart-3))/90] shadow-[hsl(var(--chart-3))/25] hover:from-[hsl(var(--chart-3))/90] hover:to-[hsl(var(--chart-3))]'
                                      : 'bg-muted hover:bg-muted/80'
                              } text-white transition-all duration-300`}
                          >
                              <Calendar className="mr-2 h-4 w-4" />
                              {lab.state === 'DEFINED_ON_CORE' ? 'Réserver Maintenant' : 'Réserver le Lab'}
                          </Button>
                      </motion.div>
                  </LabReservationDialog>

                  <TooltipProvider>
                      <Tooltip>
                          <TooltipTrigger asChild>
                              <Dialog>
                                  <DialogTrigger asChild>
                                      <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
                                          <Button variant="outline" size="sm" className="h-10 px-4 transition-colors hover:bg-white">
                                              <Eye className="mr-2 h-4 w-4" />
                                              <span className="hidden sm:inline">Schema</span>
                                          </Button>
                                      </motion.div>
                                  </DialogTrigger>
                                  <DialogContent className="max-h-[90vh] max-w-6xl overflow-hidden">
                                      <CardHeader className="px-0">
                                          <div className="flex items-center gap-3">
                                              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                                                  <Eye className="h-6 w-6 text-primary" />
                                              </div>
                                              <div>
                                                  <CardTitle className="text-xl">{lab.lab_title}</CardTitle>
                                                  <CardDescription>Aperçu de la topologie et des annotations du lab</CardDescription>
                                              </div>
                                          </div>
                                          <DialogDescription>
                                              <DialogHeader>{lab.lab_title}</DialogHeader>
                                              {lab.lab_description}.
                                          </DialogDescription>
                                      </CardHeader>

                                      <div className="relative min-h-[500px] overflow-hidden rounded-xl border bg-card shadow-lg">
                                          <div className="absolute top-4 left-4 z-10 rounded-lg border bg-background/95 px-4 py-3 shadow-sm backdrop-blur-sm">
                                              <div className="flex items-center gap-4 text-sm">
                                                  <div className="flex items-center gap-2">
                                                      <div className="h-2 w-2 rounded-full bg-green-500"></div>
                                                      <span>Aperçu Live</span>
                                                  </div>
                                                  <div className="flex items-center gap-2 text-muted-foreground">
                                                      <Network className="h-4 w-4" />
                                                      <span>{lab.node_count} nœuds</span>
                                                  </div>
                                              </div>
                                          </div>

                                          <div className="h-[500px] w-full overflow-hidden rounded-xl">
                                              <AnnotationLab labId={lab.id} />
                                          </div>
                                      </div>

                                      <div className="flex items-center justify-between pt-4 text-sm text-muted-foreground">
                                          <Badge variant="secondary" className="text-xs">
                                              Données Live
                                          </Badge>
                                      </div>
                                  </DialogContent>
                              </Dialog>
                          </TooltipTrigger>
                          <TooltipContent side="top" className="max-w-xs">
                              <div className="space-y-2">
                                  <p className="font-medium">Aperçu de l'Architecture du Lab</p>
                                  <p className="text-xs leading-relaxed">
                                      Consultez la topologie réseau complète et les annotations pour ce lab avant de réserver.
                                  </p>
                              </div>
                          </TooltipContent>
                      </Tooltip>
                  </TooltipProvider>
              </div>
          </CardContent>
      </Card>
  );
}

