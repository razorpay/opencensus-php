import React from 'react';

import AayushAggarwalImage from 'assets/rize/marketplace/testimonials/avatars/aayush-aggarwal.jpg';
import AjithTolroyGImage from 'assets/rize/marketplace/testimonials/avatars/ajith-tolroy-g.jpg';
import BasanthVermaImage from 'assets/rize/marketplace/testimonials/avatars/basanth-verma.jpg';
import DevanshJalanImage from 'assets/rize/marketplace/testimonials/avatars/devansh-jalan.jpg';

import { TestimonialCardProps } from './types';

export const TESTIMONIALS: TestimonialCardProps[] = [
  {
    name: 'Devansh Jalan',
    role: 'CEO at ArbDossier',
    avatarSrc: DevanshJalanImage,
    testimonial: (
      <>
        I enjoyed an afternoon of Founder tête-à-têtes and a fireside chat at a Razorpay Rize event
        in Bangalore. ArbDossier was the only legal tech start up among fintech, metaverse and other
        companies, which made for quite the conversation-starter.
        <br />
        <br />
        ArbDossier looks forward to rizing with Razorpay and many such events in the future.
        <br />
        <br />
        #legaltech #startup
      </>
    ),
  },
  {
    name: 'Aayush Aggarwal',
    role: 'Kapwise',
    avatarSrc: AayushAggarwalImage,
    testimonial: (
      <>
        We at Kapwise just got onboarded to RIZE by Razorpay !
        <br />
        <br />
        Love the perks, credits and a pass into a bubbling community of fellow founders. 🎉
        <br />
        <br />
        Kudos Harshil Mathur Shashank Kumar and Razorpay Rize team
        <br />
        <br />
        rize.razorpay.com
        <br />
        #founders #community #startups
      </>
    ),
  },
  {
    name: 'Ajith (Tolroy) G.',
    role: 'Founder at Grupo',
    avatarSrc: AjithTolroyGImage,
    testimonial: (
      <>
        I am very happy to meet many founders from different areas from our RazorpayRize community.
        <br />
        <br />
        All thanks to Team Rize, for their great hospitality and also giving me an opportunity to be
        part of this amazing group.
        <br />
        <br />
        Trust me this community is powerful so does what they are building.
        <br />
        <br />
        Looking forward to see many meetups like this and grow with them.
        <br />
        <br />
        #razorpay #razorpayrize #rize
        <br />
        Razorpay
      </>
    ),
  },
  {
    name: 'Basanth Verma',
    role: 'Shop EG',
    avatarSrc: BasanthVermaImage,
    testimonial:
      'Smooth onboarding, seamless incorporation and a wonderful community. Thanks to #razorpayrize team! #rizeincorporation',
  },
];
