#!/usr/bin/env bash

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# IPP - xqr - veřejné testy - 2013/2014
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# Činnost: 
# - vytvoří výstupy studentovy úlohy v daném interpretu na základě sady testů
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

TASK=xqr
INTERPRETER="php -d open_basedir=\"\""
EXTENSION=php
#INTERPRETER=python3
#EXTENSION=py

# cesty ke vstupním a výstupním souborům
LOCAL_IN_PATH="./"
#LOCAL_IN_PATH="" #Alternative 1
#LOCAL_IN_PATH=`pwd`"/" #Alternative 2
LOCAL_OUT_PATH="./"
#LOCAL_OUT_PATH="" #Alternative 1
#LOCAL_OUT_PATH=`pwd`"/" #Alternative 2
# cesta pro ukládání chybového výstupu studentského skriptu
LOG_PATH="./"


# test01: Zobrazi napovedu; Expected output: text01.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --help > ${LOCAL_OUT_PATH}test01.out 2> ${LOG_PATH}test01.err
echo -n $? > test01.!!!

# test02: Jednoduchy SELECT element FROM ROOT; Expected output: test02.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test02.in --output=${LOCAL_OUT_PATH}test02.out --qf=${LOCAL_IN_PATH}test02.qu --root=Books 2> ${LOG_PATH}test02.err
echo -n $? > test02.!!!

# test03: Vyber pomoci SELECT je omezen na dve polozky - soucasne byla odstranena hlavicka; Expected output: test03.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test03.in --output=${LOCAL_OUT_PATH}test03.out --qf=${LOCAL_IN_PATH}test03.qu --root=OnlyTwoBooks -n 2> ${LOG_PATH}test03.err
echo -n $? > test03.!!!

# test04: Vypsani pouze prazdneho Rootu pomoci omezeni limitu na 0; Expected output: test04.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test04.in --output=${LOCAL_OUT_PATH}test04.out --qf=${LOCAL_IN_PATH}test04.qu --root=EmptyRoot -n 2> ${LOG_PATH}test04.err
echo -n $? > test04.!!!

# test05: SELECT element z nasobneho nodu; Expected output: test05.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test05.in --output=${LOCAL_OUT_PATH}test05.out --qf=${LOCAL_IN_PATH}test05.qu --root=Library 2> ${LOG_PATH}test05.err
echo -n $? > test05.!!!

# test06: Vyber elementu z podelementu prvniho vyskytu prohledavaneho elementu 'library' s attributem 'my'; Expected output: test.06.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test06.in --output=${LOCAL_OUT_PATH}test06.out --qf=${LOCAL_IN_PATH}test06.qu --root=Titles 2> ${LOG_PATH}test06.err
echo -n $? > test06.!!!

# test07: Vyber z prvniho elementu obsahujiciho attribut 'my'; Expected output: test07.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --output=${LOCAL_OUT_PATH}test07.out --qf=${LOCAL_IN_PATH}test07.qu < ${LOCAL_IN_PATH}test07.in 2> ${LOG_PATH}test07.err
echo -n $? > test07.!!!

# test08: SELECT s jednoduchou podminkou; Expected output: test08.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --qf=${LOCAL_IN_PATH}test08.qu --input=${LOCAL_IN_PATH}test08.in > ${LOCAL_OUT_PATH}test08.out 2> ${LOG_PATH}test08.err
echo -n $? > test08.!!!

# test09: dalsi SELECT s jednoduchou podminkou; Expected output: test09.out; Expected return code: 0
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test09.in --output=${LOCAL_OUT_PATH}test09.out --qf=${LOCAL_IN_PATH}test09.qu 2> ${LOG_PATH}test09.err
echo -n $? > test09.!!!

# test10: SELECT s chybnou podminkou; Expected output: test10.out; Expected return code: 80
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test10.in --output=${LOCAL_OUT_PATH}test10.out --qf=${LOCAL_IN_PATH}test10.qu 2> ${LOG_PATH}test10.err
echo -n $? > test10.!!!

# test11: osetreni parametru; Expected output: test11.out; Expected return code: 1
$INTERPRETER $TASK.$EXTENSION --input=${LOCAL_IN_PATH}test11.in --output=${LOCAL_OUT_PATH}test11.out --qf=${LOCAL_OUT_PATH}test11.out --query='SELECT book FROM catalog' 2> ${LOG_PATH}test11.err
echo -n $? > test11.!!!

