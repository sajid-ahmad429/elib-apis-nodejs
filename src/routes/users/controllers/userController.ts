import { validationResult } from "express-validator";
import { config } from "../../../config/config";
import User from "../models/userModel";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import { Request, Response } from "express"; // Ensure these are imported

export const createUsers = async (req: Request, res: Response): Promise<void> => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        res.status(422).json({
            status: 422,
            message: "Validation failed",
            errors: errors.array(),
        });
        return; // Just return void here
    }

    const { firstName, lastName, userName, email, password, role, status } = req.body;

    if (!password) {
        res.status(400).json({
            status: 400,
            message: "Password is required",
        });
        return;
    }

    try {
        const existingUser = await User.findOne({ $or: [{ email }, { userName }] });
        if (existingUser) {
            let message = "User already exists";
            if (existingUser.email === email) message = "Email already in use";
            else if (existingUser.userName === userName) message = "Username already taken";

            res.status(400).json({ status: 400, message });
            return;
        }

        const hashedPassword = await bcrypt.hash(password, 10);

        const newUser = new User({
            firstName,
            lastName,
            userName,
            email,
            password: hashedPassword,
            role,
            status,
        });

        await newUser.save();

        const payload = {
            userId: newUser._id,
            email: newUser.email,
            role: newUser.role,
        };

        const token = jwt.sign(
            payload,
            config.jwt.secret || "default_secure_secret",
            { expiresIn: "7d" }
        );

        res.status(201).json({
            status: 201,
            message: "User created successfully",
            data: {
                id: newUser._id,
                firstName: newUser.firstName,
                lastName: newUser.lastName,
                userName: newUser.userName,
                email: newUser.email,
                role: newUser.role,
                status: newUser.status,
            },
            token,
        });
        return;
    } catch (error) {
        console.error("Error creating user:", error);
        res.status(500).json({
            status: 500,
            message: "Server error",
        });
        return;
    }
};

