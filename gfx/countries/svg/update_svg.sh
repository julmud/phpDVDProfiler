#!/usr/bin/env bash

curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/us.svg -o loc0.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/nz.svg -o loc1.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/au.svg -o loc2.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ca.svg -o loc3.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/gb.svg -o loc4.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/de.svg -o loc5.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/cn.svg -o loc6.svg
curl https://upload.wikimedia.org/wikipedia/commons/a/a9/Flag_of_the_Soviet_Union.svg -o loc7.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/fr.svg -o loc8.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/nl.svg -o loc9.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/es.svg -o loc10.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/se.svg -o loc11.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/no.svg -o loc12.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/it.svg -o loc13.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/dk.svg -o loc14.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/pt.svg -o loc15.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/fi.svg -o loc16.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/jp.svg -o loc17.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/kr.svg -o loc18.svg
curl https://upload.wikimedia.org/wikipedia/commons/5/5f/Flag_of_Quebec.svg -o loc19.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/za.svg -o loc20.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/hk.svg -o loc21.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ch.svg -o loc22.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/br.svg -o loc23.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/il.svg -o loc24.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/mx.svg -o loc25.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/is.svg -o loc26.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/id.svg -o loc27.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/tw.svg -o loc28.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/pl.svg -o loc29.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/be.svg -o loc30.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/tr.svg -o loc31.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ar.svg -o loc32.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/sk.svg -o loc33.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/hu.svg -o loc34.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/sg.svg -o loc35.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/cz.svg -o loc36.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/my.svg -o loc37.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/th.svg -o loc38.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/in.svg -o loc39.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/at.svg -o loc40.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/gr.svg -o loc41.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/vn.svg -o loc42.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ph.svg -o loc43.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ie.svg -o loc44.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ee.svg -o loc45.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ro.svg -o loc46.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ir.svg -o loc47.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/ru.svg -o loc48.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/cl.svg -o loc49.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/co.svg -o loc50.svg
curl https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/pe.svg -o loc51.svg

unix2dos ./*.svg

for file in ./*.svg; do svgexport "$file" $(basename "$file" svg)png pad :17 ; done
imagemin ./*.png --out-dir=../
rm ./*.png
