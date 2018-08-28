# European Cyber Security Challenge Gameboard

[![Platform LAMP](https://img.shields.io/badge/platform-LAMP-orange.svg)](#) [![License](https://img.shields.io/badge/license-EUPL--1.2-red.svg)](https://eupl.eu/) [![Twitter](https://img.shields.io/badge/twitter-@enisa__eu-blue.svg)](https://twitter.com/enisa_eu)

## Description

The complex world we live in requires that the next generation of security professionals are more than just technically capable.  Thousands of business and public agencies are looking for individuals who can accomplish their tasks in time and budget, while keeping in mind the privacy of consumers and the overall wellbeing of our society.

For this reason, the European Cyber Security Challenge (ECSC) is more than just another Capture the Flag (CTF) event. It brings a couple of new concepts and ideas that will help identify the next generation of cyber-security professionals in Europe. 

Our goal is to expose the complexity of the real world and the importance of keeping ethics in mind while performing your job. In addition, by bringing *board game* elements into the classic world of CTFs, we target a more dynamic and immersive experience   for participants. 

Have fun!

## Overview

The ECSC2018 gameplay aims to train you on the trade-offs of managing a successful cyber-security company in a competitive environment, while leveraging your technical, time management and tactical skills.

You will be playing the role of incident responders within a national cybersecurity company offering cybersecurity services to individual clients, private companies and public organisations. For each contract successfully completed, you will receive money and reputation. 

As in real life, you may sometimes choose to accept a contract from an NGO that needs help with fixing a broken CMS, while in other cases you may decide to sell 0-days to rogue governments. As in real life, your decisions have consequences. In the first instance, you may not be paid a lot, but you will earn yourself a solid reputation, while in the other, you may get cash, but miss future opportunities because of your choices. Indeed, certain contracts will require a minimum level of reputation. As in real life, some organizations just won’t do business with you if they don’t know you enough or if you have done too much business with the wrong people.

The team that will win the challenge will be the one with more cash in its wallet. 

![Screenshot](https://i.imgur.com/6jpxLsm.png)

## Concepts

The gameplay relies on a couple of very simple concepts, explained below:

*	**Jobs**: represents a series of technical tasks requested by an entity, such as an individual, company or public agency. Each job has a specific monetary value. In addition to that, there is a value assigned to the reputation you will gain or lose if you accept it. Some jobs may have restrictions, such as a level of achievement in terms of money or reputation. All jobs are available on the **Job board**.
*	**Contracts**: each job you accept is converted into a contract. The tasks described in the contract will be marked as completed by submitting a series of flags. One contract may have one or more tasks and each task is valued independently. It is required that after the completion of each contract a short report be written and uploaded on a dedicated system. Your existing contracts are available in the **Contracts** section.
*	**Chat**: the system comes with an open chat system that allows all playing entities to easily communicate messages relevant for everyone in the game; the **chat** is located on the right side of the platform;
*	**Messages**: each of the playing entities may send private messages to another playing party; in order to perform this action you will need to go to **Ranking** and click on the **send private message** icon next to the player you want to contact;
*	**Money transfer**: any team may decide to pay another team for an exchange of services, such as hints, outsourcing, etc.; in order to perform this action you will need to go to **Ranking** and click on the **send cash** icon next to the player you want to pay;
*	**Notifications**: a collection of important information, such as announcements, confirmation of money transfer, messages received, etc.
*	**Ranking**: a simple dashboard displaying the current financial status of each of the teams.

## Installation

On fresh installation of Debian or Ubuntu OS run the following commands (with `sudo` privileges):

`git clone --depth 1 https://github.com/enisaeu/ecsc-gameboard.git /var/www && cd /var/www && ./setup.sh`

Afterwards access the HTTP administrative pages by using default credentials:

`admin`:`changeme!`

## Administration

Administration pages (accessible by usage of username `admin`) are consisting of three main panels: **Teams**, **Contracts** and **Notifications**.

![Administration pages](https://i.imgur.com/eFoGTo9.png)

### Teams

Inside **Teams** panel, Administrator is able to add, delete and update individual teams by using icons inside the scoreboard's *Actions* column. Also, he is able to send private messages to each team individually.

![New team](https://i.imgur.com/QJjxfKF.png)

For example, by clicking on a *New team* action, inside the teams scoreboard, the following dialog appears:

![New team details](https://i.imgur.com/16oSjt0.png)

### Contracts

Inside **Contracts** panel, Administrator is able to add, delete and update individual contracts. Each contract can consist of individual tasks, where each task represents (scored) part of the contract.

![New contract](https://i.imgur.com/FXDVsZq.png)

For example, by clicking on a *New contract* button the following dialog appears:

![New contract details](https://i.imgur.com/dMjvREy.png)

### Notifications

Inside **Notifications** panel, Administrator can send (mass) notifications to all teams.

![Send notification](https://i.imgur.com/LxBJOLd.png)

## Usage

TODO
