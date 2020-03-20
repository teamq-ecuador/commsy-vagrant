#!/usr/bin/env bash
function setupSwarm(){
    if [[ "$(docker info | grep Swarm | sed 's/Swarm: //g')" == "inactive" ]]; then
        docker swarm init --advertise-addr=192.168.33.10;
    fi
}

setupSwarm


